<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Services\CacheService;
use App\Services\SupabaseClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase,
        private CacheService $cache
    ) {}

    public function index(Request $request): View
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Debug logging for session data
        \Log::debug('ChildController::index - Session data', [
            'user_id' => $userId,
            'has_token' => ! empty($accessToken),
            'token_length' => $accessToken ? strlen($accessToken) : 0,
            'session_id' => Session::getId(),
        ]);

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        } else {
            \Log::warning('ChildController::index - No access token found in session', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
            ]);
        }

        $children = Child::forUser($userId, $this->supabase);

        // Log the result
        \Log::debug('ChildController::index - Children query result', [
            'user_id' => $userId,
            'children_count' => $children->count(),
            'has_token' => ! empty($accessToken),
        ]);

        // If HTMX request, return partial
        if ($request->header('HX-Request')) {
            return view('children.partials.list', compact('children'));
        }

        return view('children.index', compact('children'));
    }

    public function create(): View
    {
        return view('children.partials.form', ['child' => new Child]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        \Log::debug('ChildController::store - Method called', [
            'request_data' => $request->all(),
            'user_id' => Session::get('user_id'),
            'session_id' => Session::getId(),
            'is_htmx' => $request->header('HX-Request') === 'true',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:25',
            'independence_level' => 'integer|min:1|max:4',
        ]);

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        $child = new Child($validated);
        $child->user_id = $userId; // Keep as UUID string, don't cast to int

        $saveResult = $child->save($this->supabase);

        // Log the save attempt with more details
        \Log::debug('Child save attempt', [
            'child_data' => $child->toArray(),
            'user_id' => $userId,
            'has_token' => ! empty($accessToken),
            'token_length' => $accessToken ? strlen($accessToken) : 0,
            'save_result' => $saveResult,
            'child_id_after_save' => $child->id,
        ]);

        if (! $saveResult) {
            \Log::error('Failed to save child', [
                'child_data' => $child->toArray(),
                'user_id' => $userId,
                'has_token' => ! empty($accessToken),
                'token_length' => $accessToken ? strlen($accessToken) : 0,
            ]);
            // Return error view or throw exception
            abort(500, 'Failed to create child');
        }

        // After successful save, verify the child can be retrieved
        if ($child->id) {
            $verifyChild = Child::find((string) $child->id, $this->supabase);
            \Log::debug('Child verification after save', [
                'original_child_id' => $child->id,
                'verification_result' => $verifyChild ? 'found' : 'not_found',
                'verified_child_data' => $verifyChild ? $verifyChild->toArray() : null,
            ]);
        }

        // Clear user cache and parent dashboard cache to ensure dashboard shows the new child
        $this->cache->clearUserCache($userId);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        // For HTMX requests, return the complete updated children list
        if ($request->header('HX-Request')) {
            $children = Child::forUser($userId, $this->supabase);

            return view('children.partials.list', compact('children'))
                ->with('htmx_trigger', 'childCreated');
        }

        // For regular requests, redirect
        return redirect()->route('children.index')->with('success', 'Child created successfully.');
    }

    public function show(int $id): View
    {
        $child = Child::find((string) $id, $this->supabase);

        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(404);
        }

        // Get time blocks for this child organized by day
        $timeBlocks = $child->timeBlocks($this->supabase);
        $timeBlocksByDay = [];

        // Organize by day of week
        foreach (range(1, 7) as $day) {
            $timeBlocksByDay[$day] = $timeBlocks->where('day_of_week', $day);
        }

        // Get subjects for this user
        $subjects = \App\Models\Subject::forUser(Session::get('user_id'), $this->supabase);

        return view('children.show', compact('child', 'timeBlocksByDay', 'subjects'));
    }

    public function edit(int $id): View
    {
        $child = Child::find((string) $id, $this->supabase);

        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(404);
        }

        return view('children.partials.form', compact('child'));
    }

    public function update(Request $request, int $id): View
    {
        $child = Child::find((string) $id, $this->supabase);

        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:25',
            'independence_level' => 'integer|min:1|max:4',
        ]);

        foreach ($validated as $key => $value) {
            $child->$key = $value;
        }

        $child->save($this->supabase);

        // Clear user and child caches after update
        $userId = Session::get('user_id');
        $this->cache->clearUserCache($userId);
        $this->cache->clearChildCache($child->id);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        return view('children.partials.item', compact('child'))
            ->with('htmx_trigger', 'childUpdated');
    }

    public function destroy(int $id): string
    {
        $child = Child::find((string) $id, $this->supabase);

        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(404);
        }

        $childId = $child->id;
        $userId = Session::get('user_id');

        $child->delete($this->supabase);

        // Clear user and child caches after deletion
        $this->cache->clearUserCache($userId);
        $this->cache->clearChildCache($childId);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'childDeleted')->send();

        return '';
    }
}
