<?php

namespace App\Http\Controllers;

use App\Models\CatchUpSession;
use App\Models\Child;
use App\Models\Review;
use App\Models\Session;
use App\Services\CacheService;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session as LaravelSession;

class DashboardController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase,
        private CacheService $cache
    ) {}

    /**
     * Parent Dashboard - Multi-child overview with planning tools
     */
    public function parentDashboard()
    {
        $userId = LaravelSession::get('user_id');
        $accessToken = LaravelSession::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        // Check if PIN is set up for kids mode
        $pinIsSet = $this->checkKidsModePinStatus($userId);

        // Try to get cached dashboard data
        $cacheKey = "parent_dashboard_{$userId}";
        $cachedData = $this->cache->cacheExpensiveQuery($cacheKey, function () use ($userId) {
            $children = $this->cache->getUserChildren($userId) ?? Child::forUser($userId, $this->supabase);

            if (! $this->cache->getUserChildren($userId)) {
                $this->cache->cacheUserChildren($userId, $children);
            }

            $dashboardData = [];

            foreach ($children as $child) {
                $childDashboard = $this->cache->getChildDashboard($child->id);

                if (! $childDashboard) {
                    $todaySessions = $this->getTodaySessionsForChild($child->id);
                    $reviewQueue = Review::getReviewQueue($child->id, $this->supabase)->take(10);
                    $catchUpSessions = CatchUpSession::pending($child->id, $this->supabase)->take(5);

                    $childDashboard = [
                        'child' => $child,
                        'today_sessions' => $todaySessions,
                        'review_queue_count' => $reviewQueue->count(),
                        'catch_up_count' => $catchUpSessions->count(),
                        'capacity_status' => $this->getCapacityStatus($child->id),
                        'weekly_progress' => $this->getWeeklyProgress($child->id),
                    ];

                    $this->cache->cacheChildDashboard($child->id, $childDashboard);
                }

                $dashboardData[] = $childDashboard;
            }

            return [
                'children' => $children,
                'dashboard_data' => $dashboardData,
                'week_start' => Carbon::now()->startOfWeek(),
            ];
        }, 300); // 5-minute cache

        // Add PIN status to the cached data
        $cachedData['pin_is_set'] = $pinIsSet;

        return view('dashboard.parent', $cachedData);
    }

    /**
     * Child Today View - Simple interface for daily tasks
     */
    public function childToday(Request $request, $childId)
    {
        $child = $request->attributes->get('child');

        // Get today's sessions (max 3 as per requirements)
        $todaySessions = $this->getTodaySessionsForChild($child->id)->take(3);

        // Get review queue (5-10 items max)
        $reviewQueue = Review::getReviewQueue($child->id, $this->supabase)->take(10);

        // Get upcoming sessions for the week (for level 3+ independence)
        $weekSessions = [];
        if ($child->canMoveSessionsInWeek()) {
            $weekSessions = $this->getWeekSessionsForChild($child->id);
        }

        return view('dashboard.child-today', [
            'child' => $child,
            'today_sessions' => $todaySessions,
            'review_queue' => $reviewQueue,
            'week_sessions' => $weekSessions,
            'can_reorder' => $child->canReorderTasks(),
            'can_move_weekly' => $child->canMoveSessionsInWeek(),
        ]);
    }

    /**
     * Get today's scheduled sessions for a child
     */
    private function getTodaySessionsForChild(int $childId): \Illuminate\Support\Collection
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeekIso; // 1=Monday, 7=Sunday

        return Session::forChildAndDay($childId, $dayOfWeek, $this->supabase)
            ->where('status', 'scheduled');
    }

    /**
     * Get week's sessions for a child
     */
    private function getWeekSessionsForChild(int $childId): array
    {
        $weekSessions = [];
        $startOfWeek = Carbon::now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dayOfWeek = $day->dayOfWeekIso;

            $sessions = Session::forChildAndDay($childId, $dayOfWeek, $this->supabase)
                ->where('status', 'scheduled');

            $weekSessions[$dayOfWeek] = [
                'date' => $day,
                'day_name' => $day->format('l'),
                'sessions' => $sessions,
            ];
        }

        return $weekSessions;
    }

    /**
     * Get capacity status for a child
     */
    private function getCapacityStatus(int $childId): array
    {
        // Get this week's scheduled sessions
        $thisWeekSessions = collect();
        $startOfWeek = Carbon::now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $dayOfWeek = $startOfWeek->copy()->addDays($i)->dayOfWeekIso;
            $daySessions = Session::forChildAndDay($childId, $dayOfWeek, $this->supabase)
                ->where('status', 'scheduled');
            $thisWeekSessions = $thisWeekSessions->merge($daySessions);
        }

        $totalMinutes = $thisWeekSessions->sum('estimated_minutes');
        $completedMinutes = $thisWeekSessions->where('status', 'completed')->sum('estimated_minutes');
        $catchUpCount = CatchUpSession::pending($childId, $this->supabase)->count();

        return [
            'total_sessions' => $thisWeekSessions->count(),
            'completed_sessions' => $thisWeekSessions->where('status', 'completed')->count(),
            'total_minutes' => $totalMinutes,
            'completed_minutes' => $completedMinutes,
            'completion_percentage' => $totalMinutes > 0 ? round(($completedMinutes / $totalMinutes) * 100) : 0,
            'catch_up_count' => $catchUpCount,
            'status' => $this->determineCapacityStatus($totalMinutes, $completedMinutes, $catchUpCount),
        ];
    }

    /**
     * Determine capacity status color/label
     */
    private function determineCapacityStatus(int $totalMinutes, int $completedMinutes, int $catchUpCount): array
    {
        $completionRate = $totalMinutes > 0 ? ($completedMinutes / $totalMinutes) : 0;

        if ($catchUpCount > 5) {
            return ['label' => 'Overloaded', 'color' => 'red'];
        } elseif ($catchUpCount > 2) {
            return ['label' => 'Behind', 'color' => 'orange'];
        } elseif ($completionRate >= 0.8) {
            return ['label' => 'On Track', 'color' => 'green'];
        } elseif ($completionRate >= 0.6) {
            return ['label' => 'Moderate', 'color' => 'blue'];
        } else {
            return ['label' => 'Needs Attention', 'color' => 'yellow'];
        }
    }

    /**
     * Get weekly progress for a child
     */
    private function getWeeklyProgress(int $childId): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $dailyProgress = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dayOfWeek = $day->dayOfWeekIso;

            $sessions = Session::forChildAndDay($childId, $dayOfWeek, $this->supabase);
            $completed = $sessions->where('status', 'completed')->count();
            $total = $sessions->count();

            $dailyProgress[] = [
                'day' => $day->format('D'),
                'date' => $day->format('M j'),
                'completed' => $completed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        }

        return $dailyProgress;
    }

    /**
     * Parent action: Skip day for child
     */
    public function skipDay(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $session = Session::find($validated['session_id'], $this->supabase);
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Verify ownership
        $child = Child::find($session->child_id, $this->supabase);
        if ($child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $date = Carbon::parse($validated['date']);
        $catchUpSession = $session->skipDay($date, $validated['reason'], $this->supabase);

        return response()->json([
            'success' => true,
            'message' => 'Session moved to catch-up queue',
            'catch_up_session' => $catchUpSession->toArray(),
        ]);
    }

    /**
     * Parent action: Move theme between weeks
     */
    public function moveTheme(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'new_date' => 'required|date',
            'new_day_of_week' => 'required|integer|min:1|max:7',
        ]);

        $session = Session::find($validated['session_id'], $this->supabase);
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Verify ownership
        $child = Child::find($session->child_id, $this->supabase);
        if ($child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Update session
        $session->scheduled_day_of_week = $validated['new_day_of_week'];
        $session->scheduled_date = Carbon::parse($validated['new_date']);
        $session->save($this->supabase);

        return response()->json([
            'success' => true,
            'message' => 'Session moved successfully',
            'session' => $session->toArray(),
        ]);
    }

    /**
     * Bulk mark sessions as complete
     */
    public function bulkCompleteToday(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer',
            'evidence_notes' => 'nullable|string',
        ]);

        $child = Child::find($validated['child_id'], $this->supabase);
        if (! $child || $child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $todaySessions = $this->getTodaySessionsForChild($child->id);
        $completedCount = 0;

        foreach ($todaySessions as $session) {
            $session->status = 'completed';
            $session->completed_at = Carbon::now();
            $session->evidence_notes = $validated['evidence_notes'] ?? 'Bulk completed by parent';
            if ($session->save($this->supabase)) {
                $completedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Marked {$completedCount} sessions as complete",
            'completed_count' => $completedCount,
        ]);
    }

    /**
     * Complete a single session
     */
    public function completeSession(Request $request, $sessionId)
    {
        $session = Session::find($sessionId, $this->supabase);
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Verify ownership
        $child = Child::find($session->child_id, $this->supabase);
        if ($child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $session->status = 'completed';
        $session->completed_at = Carbon::now();
        $session->save($this->supabase);

        // Clear related caches
        $this->cache->clearSessionCaches($session->child_id);
        $this->cache->clearChildCache($session->child_id);

        return response()->json([
            'success' => true,
            'message' => 'Session completed!',
            'session' => $session->toArray(),
        ]);
    }

    /**
     * Reorder today's sessions (for independence level 2+)
     */
    public function reorderTodaySessions(Request $request, $childId)
    {
        $validated = $request->validate([
            'session_order' => 'required|array',
            'session_order.*' => 'integer',
        ]);

        $child = Child::find($childId, $this->supabase);
        if (! $child || $child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (! $child->canReorderTasks()) {
            return response()->json(['error' => 'Child independence level does not allow reordering'], 403);
        }

        // Update session orders
        foreach ($validated['session_order'] as $order => $sessionId) {
            $session = Session::find($sessionId, $this->supabase);
            if ($session && $session->child_id === $child->id) {
                // You could add an order field to sessions if needed
                // For now, this would be handled by the frontend
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sessions reordered successfully',
        ]);
    }

    /**
     * Move session within week (for independence level 3+)
     */
    public function moveSessionInWeek(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'new_day_of_week' => 'required|integer|min:1|max:7',
        ]);

        $session = Session::find($validated['session_id'], $this->supabase);
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $child = Child::find($session->child_id, $this->supabase);
        if (! $child || $child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (! $child->canMoveSessionsInWeek()) {
            return response()->json(['error' => 'Child independence level does not allow moving sessions'], 403);
        }

        $session->scheduled_day_of_week = $validated['new_day_of_week'];
        $session->save($this->supabase);

        return response()->json([
            'success' => true,
            'message' => 'Session moved successfully',
            'session' => $session->toArray(),
        ]);
    }

    /**
     * Update child independence level
     */
    public function updateIndependenceLevel(Request $request, $childId)
    {
        $validated = $request->validate([
            'independence_level' => 'required|integer|min:1|max:4',
        ]);

        $child = Child::find($childId, $this->supabase);
        if (! $child || $child->user_id !== LaravelSession::get('user_id')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $child->independence_level = $validated['independence_level'];
        $child->save($this->supabase);

        return response()->json([
            'success' => true,
            'message' => 'Independence level updated',
            'child' => $child->toArray(),
        ]);
    }

    /**
     * Check if kids mode PIN is set up for the user
     */
    private function checkKidsModePinStatus(string $userId): bool
    {
        try {
            $preferences = $this->supabase->from('user_preferences')
                ->select('kids_mode_pin')
                ->eq('user_id', $userId)
                ->single();

            return ! empty($preferences['kids_mode_pin']);
        } catch (\Exception $e) {
            \Log::error('Failed to check kids mode PIN status', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
