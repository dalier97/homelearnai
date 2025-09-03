<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function index(Request $request): View
    {
        $tasks = Task::forUser(Session::get('user_id'), $this->supabase);

        // Filter by search
        if ($search = $request->get('search')) {
            $tasks = $tasks->filter(function ($task) use ($search) {
                return str_contains(strtolower($task->title), strtolower($search)) ||
                       str_contains(strtolower($task->description ?? ''), strtolower($search));
            });
        }

        // Filter by priority
        if ($priority = $request->get('priority')) {
            $tasks = $tasks->where('priority', $priority);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $tasks = $tasks->where('status', $status);
        }

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

        $task = new Task($validated);
        $task->user_id = Session::get('user_id');
        $task->save($this->supabase);

        // Return the new task item for HTMX
        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskCreated');
    }

    public function edit(int $id): View
    {
        $task = Task::find($id, $this->supabase);

        if (! $task || $task->user_id !== Session::get('user_id')) {
            abort(404);
        }

        return view('tasks.partials.form', compact('task'));
    }

    public function update(Request $request, int $id): View
    {
        $task = Task::find($id, $this->supabase);

        if (! $task || $task->user_id !== Session::get('user_id')) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        foreach ($validated as $key => $value) {
            $task->$key = $value;
        }

        $task->save($this->supabase);

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskUpdated');
    }

    public function toggle(int $id): View
    {
        $task = Task::find($id, $this->supabase);

        if (! $task || $task->user_id !== Session::get('user_id')) {
            abort(404);
        }

        $task->toggleComplete();
        $task->save($this->supabase);

        return view('tasks.partials.item', compact('task'))
            ->with('htmx_trigger', 'taskToggled');
    }

    public function destroy(int $id): string
    {
        $task = Task::find($id, $this->supabase);

        if (! $task || $task->user_id !== Session::get('user_id')) {
            abort(404);
        }

        $task->delete($this->supabase);

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'taskDeleted')->send();

        return '';
    }
}
