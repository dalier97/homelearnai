<?php

namespace App\Http\Controllers;

use App\Models\CatchUpSession;
use App\Models\Child;
use App\Models\Review;
use App\Models\Session;
use App\Models\User;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private CacheService $cache
    ) {}

    /**
     * Parent Dashboard - Multi-child overview with planning tools
     */
    public function parentDashboard()
    {
        $userId = auth()->id();

        // Check if PIN is set up for kids mode
        $pinIsSet = $this->checkKidsModePinStatus($userId);

        // Check if user has any children
        $children = $this->cache->getUserChildren($userId) ?? Child::forUser($userId);

        if ($children->isEmpty()) {
            // Only redirect to onboarding if user hasn't completed it yet
            $user = auth()->user();
            $userPrefs = $user->getPreferences();

            if (! $userPrefs->onboarding_completed) {
                return redirect('/onboarding');
            }

            // User completed onboarding but has no children - show empty dashboard with CTA
            return view('dashboard.parent', [
                'children' => collect(),
                'dashboard_data' => [],
                'pin_is_set' => $pinIsSet,
                'show_add_children_cta' => true,
            ]);
        }

        // Try to get cached dashboard data
        $cacheKey = "parent_dashboard_{$userId}";
        $cachedData = $this->cache->cacheExpensiveQuery($cacheKey, function () use ($userId, $children) {
            if (! $this->cache->getUserChildren($userId)) {
                $this->cache->cacheUserChildren($userId, $children);
            }

            $dashboardData = [];

            foreach ($children as $child) {
                /** @var \App\Models\Child $child */
                $childDashboard = $this->cache->getChildDashboard($child->id);

                if (! $childDashboard) {
                    $todaySessions = $this->getTodaySessionsForChild($child->id);
                    // TODO: Convert to Eloquent when Review model is converted
                    $reviewQueue = collect(); // Review::getReviewQueue($child->id)->take(10);
                    // TODO: Convert to Eloquent when CatchUpSession model is converted
                    $catchUpSessions = collect(); // CatchUpSession::pending($child->id)->take(5);

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
        // TODO: Convert to Eloquent when Review model is converted
        $reviewQueue = collect(); // Review::getReviewQueue($child->id)->take(10);

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
        // TODO: Convert to Eloquent when Session model is converted
        return collect(); // Session::forChildAndDay($childId, $dayOfWeek)->where('status', 'scheduled');
    }

    /**
     * Get week's sessions for a child
     */
    private function getWeekSessionsForChild(int $childId): array
    {
        // TODO: Convert to Eloquent when Session model is converted
        return []; // Return empty array until Session model is converted
    }

    /**
     * Get capacity status for a child
     */
    private function getCapacityStatus(int $childId): array
    {
        // TODO: Convert to Eloquent when Session and CatchUpSession models are converted
        return [
            'total_sessions' => 0,
            'completed_sessions' => 0,
            'total_minutes' => 0,
            'completed_minutes' => 0,
            'completion_percentage' => 0,
            'catch_up_count' => 0,
            'status' => $this->determineCapacityStatus(0, 0, 0),
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
        // TODO: Convert to Eloquent when Session model is converted
        $startOfWeek = Carbon::now()->startOfWeek();
        $dailyProgress = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);

            $dailyProgress[] = [
                'day' => $day->format('D'),
                'date' => $day->format('M j'),
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
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

        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Skip day functionality temporarily disabled during migration',
        ], 503);
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

        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Move theme functionality temporarily disabled during migration',
        ], 503);
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

        $child = Child::findOrFail($validated['child_id']);
        if ($child->user_id != auth()->id()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Bulk complete functionality temporarily disabled during migration',
        ], 503);
    }

    /**
     * Complete a single session
     */
    public function completeSession(Request $request, $sessionId)
    {
        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Complete session functionality temporarily disabled during migration',
        ], 503);
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

        $child = Child::findOrFail($childId);
        if ($child->user_id != auth()->id()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (! $child->canReorderTasks()) {
            return response()->json(['error' => 'Child independence level does not allow reordering'], 403);
        }

        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Reorder sessions functionality temporarily disabled during migration',
        ], 503);
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

        // TODO: Convert to Eloquent when Session model is converted
        return response()->json([
            'error' => 'Move session functionality temporarily disabled during migration',
        ], 503);
    }

    /**
     * Update child independence level
     */
    public function updateIndependenceLevel(Request $request, $childId)
    {
        $validated = $request->validate([
            'independence_level' => 'required|integer|min:1|max:4',
        ]);

        $child = Child::findOrFail($childId);
        if ($child->user_id != auth()->id()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $child->independence_level = $validated['independence_level'];
        $child->save();

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
            $user = User::find($userId);
            if (! $user) {
                return false;
            }

            $userPrefs = $user->getPreferences();

            return $userPrefs->hasPinSetup();
        } catch (\Exception $e) {
            \Log::error('Failed to check kids mode PIN status', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
