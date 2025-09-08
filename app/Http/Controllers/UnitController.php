<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Unit;
use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnitController extends Controller
{
    private SupabaseClient $supabase;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of units for a subject.
     */
    public function index(Request $request, int $subjectId)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return redirect()->route('login')->with('error', 'Please log in to continue.');
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return redirect()->route('subjects.index')->with('error', 'Subject not found.');
            }

            $units = Unit::forSubject($subjectId, $this->supabase);

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return view('units.partials.units-list', compact('units', 'subject'));
            }

            return view('units.index', compact('units', 'subject'));
        } catch (\Exception $e) {
            Log::error('Error fetching units: '.$e->getMessage());

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error loading units. Please try again.').'</div>', 500);
            }

            return redirect()->route('subjects.index')->with('error', 'Unable to load units. Please try again.');
        }
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create(Request $request, int $subjectId)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            if ($request->header('HX-Request')) {
                return view('units.partials.create-form', compact('subject'));
            }

            return view('units.create', compact('subject'));
        } catch (\Exception $e) {
            Log::error('Error loading unit creation form: '.$e->getMessage());

            return response('Unable to load form.', 500);
        }
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request, int $subjectId)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'target_completion_date' => 'nullable|date',
            ]);

            $unit = new Unit([
                'subject_id' => $subjectId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'target_completion_date' => $validated['target_completion_date'] ?
                    \Carbon\Carbon::parse($validated['target_completion_date']) : null,
            ]);

            if ($unit->save($this->supabase)) {
                Log::info('Unit created successfully', ['unit_id' => $unit->id, 'name' => $unit->name, 'subject_id' => $subjectId]);

                if ($request->header('HX-Request')) {
                    // Return updated units list
                    $units = Unit::forSubject($subjectId, $this->supabase);
                    Log::info('Returning units list for HTMX', ['units_count' => $units->count()]);

                    return view('units.partials.units-list', compact('units', 'subject'));
                }

                return redirect()->route('subjects.show', $subjectId)->with('success', 'Unit created successfully.');
            } else {
                Log::error('Failed to save unit', ['unit_name' => $unit->name, 'subject_id' => $subjectId]);
                throw new \Exception('Failed to save unit');
            }
        } catch (\Exception $e) {
            Log::error('Error creating unit: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error creating unit. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to create unit. Please try again.']);
        }
    }

    /**
     * Display the specified unit.
     */
    public function show(Request $request, int $subjectId, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return redirect()->route('login');
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return redirect()->route('subjects.index')->with('error', 'Subject not found.');
            }

            $unit = Unit::find((string) $id, $this->supabase);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return redirect()->route('subjects.show', $subjectId)->with('error', 'Unit not found.');
            }

            $topics = $unit->topics($this->supabase);

            if ($request->header('HX-Request')) {
                return view('units.partials.unit-details', compact('unit', 'subject', 'topics'));
            }

            return view('units.show', compact('unit', 'subject', 'topics'));
        } catch (\Exception $e) {
            Log::error('Error fetching unit: '.$e->getMessage());

            return redirect()->route('subjects.show', $subjectId)->with('error', 'Unable to load unit. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Request $request, int $subjectId, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $unit = Unit::find((string) $id, $this->supabase);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return response('Unit not found', 404);
            }

            if ($request->header('HX-Request')) {
                return view('units.partials.edit-form', compact('unit', 'subject'));
            }

            return view('units.edit', compact('unit', 'subject'));
        } catch (\Exception $e) {
            Log::error('Error loading unit for edit: '.$e->getMessage());

            return response('Unable to load unit for editing.', 500);
        }
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, int $subjectId, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $unit = Unit::find((string) $id, $this->supabase);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return response('Unit not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'target_completion_date' => 'nullable|date',
            ]);

            $unit->name = $validated['name'];
            $unit->description = $validated['description'] ?? null;
            $unit->target_completion_date = $validated['target_completion_date'] ?
                \Illuminate\Support\Carbon::parse($validated['target_completion_date']) : null;

            if ($unit->save($this->supabase)) {
                if ($request->header('HX-Request')) {
                    // Return updated units list
                    $units = Unit::forSubject($subjectId, $this->supabase);

                    return view('units.partials.units-list', compact('units', 'subject'));
                }

                return redirect()->route('subjects.show', $subjectId)->with('success', 'Unit updated successfully.');
            } else {
                throw new \Exception('Failed to update unit');
            }
        } catch (\Exception $e) {
            Log::error('Error updating unit: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error updating unit. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to update unit. Please try again.']);
        }
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Request $request, int $subjectId, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find((string) $subjectId, $this->supabase);
            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $unit = Unit::find((string) $id, $this->supabase);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return response('Unit not found', 404);
            }

            // Check if unit has topics - prevent deletion if it has topics
            $topics = $unit->topics($this->supabase);
            if ($topics->count() > 0) {
                if ($request->header('HX-Request')) {
                    return response('<div class="text-red-500">'.__('Cannot delete unit with existing topics. Please delete all topics first.').'</div>', 400);
                }

                return back()->withErrors(['error' => 'Cannot delete unit with existing topics. Please delete all topics first.']);
            }

            if ($unit->delete($this->supabase)) {
                if ($request->header('HX-Request')) {
                    // Return updated units list
                    $units = Unit::forSubject($subjectId, $this->supabase);

                    return view('units.partials.units-list', compact('units', 'subject'));
                }

                return redirect()->route('subjects.show', $subjectId)->with('success', 'Unit deleted successfully.');
            } else {
                throw new \Exception('Failed to delete unit');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting unit: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error deleting unit. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to delete unit. Please try again.']);
        }
    }
}
