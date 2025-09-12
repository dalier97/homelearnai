<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function __construct(
        private CacheService $cache
    ) {}

    public function index(Request $request): View
    {
        // Get authenticated user's children using Eloquent
        $children = Auth::user()->children()->orderBy('name')->get();

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:25',
            'independence_level' => 'integer|min:1|max:4',
        ]);

        // Log the creation attempt
        \Log::info('ChildController::store - Attempting to create child', [
            'user_id' => Auth::id(),
            'data' => $validated,
            'is_htmx' => $request->header('HX-Request') ? true : false,
        ]);

        // Create child using Eloquent with authenticated user
        $child = Auth::user()->children()->create($validated);
        /** @var \App\Models\Child $child */
        \Log::info('ChildController::store - Child created', [
            'child_id' => $child->id,
            'child_name' => $child->name,
            'user_id' => Auth::id(),
        ]);

        // Clear user cache to ensure dashboard shows the new child
        $userId = Auth::id();
        $this->cache->clearUserCache($userId);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        // For HTMX requests, return the complete updated children list
        if ($request->header('HX-Request')) {
            $children = Auth::user()->children()->orderBy('name')->get();

            \Log::info('ChildController::store - Returning updated children list', [
                'children_count' => $children->count(),
                'children_names' => $children->pluck('name')->toArray(),
            ]);

            return view('children.partials.list', compact('children'))
                ->with('htmx_trigger', 'childCreated');
        }

        // For regular requests, redirect
        return redirect()->route('children.index')->with('success', 'Child created successfully.');
    }

    public function show(int $id): View
    {
        /** @var \App\Models\Child $child */
        $child = Auth::user()->children()->findOrFail($id);

        // Get time blocks for this child organized by day
        $timeBlocks = $child->timeBlocks;
        $timeBlocksByDay = [];

        // Organize by day of week
        foreach (range(1, 7) as $day) {
            $timeBlocksByDay[$day] = $timeBlocks->where('day_of_week', $day);
        }

        // Get subjects for this child
        $subjects = $child->subjects;

        return view('children.show', compact('child', 'timeBlocksByDay', 'subjects'));
    }

    public function edit(int $id): View
    {
        $child = Auth::user()->children()->findOrFail($id);

        return view('children.partials.form', compact('child'));
    }

    public function update(Request $request, int $id): View
    {
        /** @var \App\Models\Child $child */
        $child = Auth::user()->children()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:25',
            'independence_level' => 'integer|min:1|max:4',
        ]);

        $child->update($validated);

        // Clear user and child caches after update
        $userId = Auth::id();
        $this->cache->clearUserCache($userId);
        $this->cache->clearChildCache($child->id);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        return view('children.partials.item', compact('child'))
            ->with('htmx_trigger', 'childUpdated');
    }

    public function destroy(int $id): string
    {
        /** @var \App\Models\Child $child */
        $child = Auth::user()->children()->findOrFail($id);

        $childId = $child->id;
        $userId = Auth::id();

        $child->delete();

        // Clear user and child caches after deletion
        $this->cache->clearUserCache($userId);
        $this->cache->clearChildCache($childId);
        \Illuminate\Support\Facades\Cache::forget("parent_dashboard_{$userId}");

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'childDeleted')->send();

        return '';
    }
}
