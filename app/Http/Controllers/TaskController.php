<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    // No constructor needed - using Eloquent

    public function index(Request $request): View
    {
        $query = Task::forUser(auth()->id());

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', '%'.$search.'%')
                    ->orWhere('description', 'ILIKE', '%'.$search.'%');
            });
        }

        // Filter by priority
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tasks = $query->get();

        // If HTMX request, return partial
        if ($request->header('HX-Request')) {
            return view('tasks.partials.list', compact('tasks'));
        }

        return view('tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        return view('tasks.partials.form', ['task' => new Task]);
    }

    public function store(Request $request): View
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $validated['user_id'] = auth()->id();
        $task = Task::create($validated);

        // Return the new task item for HTMX
        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskCreated');
    }

    public function edit(int $id): View
    {
        $task = Task::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('tasks.partials.form', compact('task'));
    }

    public function update(Request $request, int $id): View
    {
        $task = Task::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskUpdated');
    }

    public function toggle(int $id): View
    {
        $task = Task::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $task->toggleComplete();

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskToggled');
    }

    public function destroy(int $id): string
    {
        $task = Task::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $task->delete();

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'taskDeleted')->send();

        return '';
    }
}
