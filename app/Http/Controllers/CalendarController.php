<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\ReviewSlot;
use App\Models\TimeBlock;
use App\Services\SupabaseClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function index(Request $request): View
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        $children = Child::forUser($userId, $this->supabase);

        // Default to first child or selected child
        $selectedChildId = $request->get('child_id', $children->first()?->id);
        $selectedChild = $selectedChildId ? Child::find((string) $selectedChildId, $this->supabase) : null;

        // Get time blocks for the selected child
        $timeBlocks = $selectedChild ? $selectedChild->timeBlocks($this->supabase) : collect([]);

        // Get review slots for the selected child
        $reviewSlots = $selectedChild ? ReviewSlot::forChild($selectedChild->id, $this->supabase) : collect([]);

        // Organize time blocks and review slots by day for calendar view
        $timeBlocksByDay = [];
        $reviewSlotsByDay = [];
        foreach (range(1, 7) as $day) {
            $timeBlocksByDay[$day] = $timeBlocks->where('day_of_week', $day)->sortBy('start_time');
            $reviewSlotsByDay[$day] = $reviewSlots->where('day_of_week', $day)->where('is_active', true)->sortBy('start_time');
        }

        // If HTMX request, return partial calendar
        if ($request->header('HX-Request')) {
            return view('calendar.partials.grid', compact('timeBlocksByDay', 'reviewSlotsByDay', 'selectedChild'));
        }

        return view('calendar.index', compact('children', 'selectedChild', 'timeBlocksByDay', 'reviewSlotsByDay'));
    }

    public function create(Request $request): View
    {
        $children = Child::forUser(Session::get('user_id'), $this->supabase);
        $selectedChildId = $request->get('child_id');
        $selectedDay = $request->get('day_of_week', 1);

        return view('calendar.partials.time-block-form', [
            'timeBlock' => new TimeBlock(['day_of_week' => $selectedDay]),
            'children' => $children,
            'selectedChildId' => $selectedChildId,
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'child_id' => 'required|integer|exists:children,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'end_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/|after:start_time',
            'label' => 'required|string|max:255',
        ]);

        // Verify child belongs to the current user
        $child = Child::find((string) $validated['child_id'], $this->supabase);
        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(403);
        }

        // Check for overlapping time blocks
        $existingBlocks = TimeBlock::forChildAndDay($validated['child_id'], $validated['day_of_week'], $this->supabase);
        $newBlock = new TimeBlock([
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'day_of_week' => $validated['day_of_week'],
        ]);

        foreach ($existingBlocks as $existingBlock) {
            if ($newBlock->overlapsWith($existingBlock)) {
                return back()->withErrors(['time' => 'Time block overlaps with existing block']);
            }
        }

        // Create the time block
        $timeBlock = new TimeBlock([
            'child_id' => $validated['child_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'label' => $validated['label'],
        ]);

        $timeBlock->save($this->supabase);

        // Return updated calendar for the specific day
        $timeBlocksForDay = TimeBlock::forChildAndDay($validated['child_id'], $validated['day_of_week'], $this->supabase);

        return view('calendar.partials.day-column', [
            'day' => $validated['day_of_week'],
            'timeBlocks' => $timeBlocksForDay,
            'selectedChild' => $child,
        ])->with('htmx_trigger', 'timeBlockCreated');
    }

    public function edit(int $id): View
    {
        $timeBlock = TimeBlock::find((string) $id, $this->supabase);

        if (! $timeBlock) {
            abort(404);
        }

        // Verify the time block belongs to user's child
        $child = $timeBlock->child($this->supabase);
        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(403);
        }

        $children = Child::forUser(Session::get('user_id'), $this->supabase);

        return view('calendar.partials.time-block-form', [
            'timeBlock' => $timeBlock,
            'children' => $children,
            'selectedChildId' => $timeBlock->child_id,
        ]);
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $timeBlock = TimeBlock::find((string) $id, $this->supabase);

        if (! $timeBlock) {
            abort(404);
        }

        // Verify the time block belongs to user's child
        $child = $timeBlock->child($this->supabase);
        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'child_id' => 'required|integer|exists:children,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'end_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/|after:start_time',
            'label' => 'required|string|max:255',
        ]);

        // Check for overlapping time blocks (excluding this one)
        $existingBlocks = TimeBlock::forChildAndDay($validated['child_id'], $validated['day_of_week'], $this->supabase)
            ->where('id', '!=', $id);

        $updatedBlock = new TimeBlock([
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'day_of_week' => $validated['day_of_week'],
        ]);

        foreach ($existingBlocks as $existingBlock) {
            if ($updatedBlock->overlapsWith($existingBlock)) {
                return back()->withErrors(['time' => 'Time block overlaps with existing block']);
            }
        }

        // Update the time block
        foreach ($validated as $key => $value) {
            if ($key === 'start_time' || $key === 'end_time') {
                $timeBlock->$key = $value.':00';
            } else {
                $timeBlock->$key = $value;
            }
        }

        $timeBlock->save($this->supabase);

        // Return updated calendar for the specific day
        $timeBlocksForDay = TimeBlock::forChildAndDay($validated['child_id'], $validated['day_of_week'], $this->supabase);

        return view('calendar.partials.day-column', [
            'day' => $validated['day_of_week'],
            'timeBlocks' => $timeBlocksForDay,
            'selectedChild' => Child::find((string) $validated['child_id'], $this->supabase),
        ])->with('htmx_trigger', 'timeBlockUpdated');
    }

    public function destroy(int $id): View
    {
        $timeBlock = TimeBlock::find((string) $id, $this->supabase);

        if (! $timeBlock) {
            abort(404);
        }

        // Verify the time block belongs to user's child
        $child = $timeBlock->child($this->supabase);
        if (! $child || $child->user_id !== Session::get('user_id')) {
            abort(403);
        }

        $day = $timeBlock->day_of_week;
        $childId = $timeBlock->child_id;

        $timeBlock->delete($this->supabase);

        // Return updated calendar for the specific day
        $timeBlocksForDay = TimeBlock::forChildAndDay($childId, $day, $this->supabase);

        return view('calendar.partials.day-column', [
            'day' => $day,
            'timeBlocks' => $timeBlocksForDay,
            'selectedChild' => $child,
        ])->with('htmx_trigger', 'timeBlockDeleted');
    }
}
