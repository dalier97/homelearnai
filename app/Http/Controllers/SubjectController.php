<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubjectController extends Controller
{
    private SupabaseClient $supabase;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of subjects.
     */
    public function index(Request $request)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return redirect()->route('login')->with('error', 'Please log in to continue.');
            }

            $subjects = Subject::forUser($userId, $this->supabase);
            $showQuickStart = $subjects->isEmpty(); // Add this flag

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return view('subjects.partials.subjects-list', compact('subjects', 'showQuickStart'));
            }

            return view('subjects.index', compact('subjects', 'showQuickStart'));
        } catch (\Exception $e) {
            Log::error('Error fetching subjects: '.$e->getMessage());

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error loading subjects. Please try again.').'</div>', 500);
            }

            return back()->with('error', 'Unable to load subjects. Please try again.');
        }
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create(Request $request)
    {
        $colors = Subject::getColorOptions();

        if ($request->header('HX-Request')) {
            return view('subjects.partials.create-form', compact('colors'));
        }

        return view('subjects.create', compact('colors'));
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string',
            ]);

            // Validate color is in allowed options
            $allowedColors = array_keys(Subject::getColorOptions());
            if (! in_array($validated['color'], $allowedColors)) {
                return back()->withErrors(['color' => 'Invalid color selected.']);
            }

            $subject = new Subject([
                'name' => $validated['name'],
                'color' => $validated['color'],
                'user_id' => $userId,
            ]);

            if ($subject->save($this->supabase)) {
                if ($request->header('HX-Request')) {
                    // Return updated subjects list
                    $subjects = Subject::forUser($userId, $this->supabase);

                    return view('subjects.partials.subjects-list', compact('subjects'));
                }

                return redirect()->route('subjects.index')->with('success', 'Subject created successfully.');
            } else {
                throw new \Exception('Failed to save subject');
            }
        } catch (\Exception $e) {
            Log::error('Error creating subject: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error creating subject. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to create subject. Please try again.']);
        }
    }

    /**
     * Display the specified subject.
     */
    public function show(Request $request, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return redirect()->route('login');
            }

            $subject = Subject::find($id, $this->supabase);

            if (! $subject || $subject->user_id !== $userId) {
                return redirect()->route('subjects.index')->with('error', 'Subject not found.');
            }

            $units = $subject->units($this->supabase);

            if ($request->header('HX-Request')) {
                return view('subjects.partials.subject-details', compact('subject', 'units'));
            }

            return view('subjects.show', compact('subject', 'units'));
        } catch (\Exception $e) {
            Log::error('Error fetching subject: '.$e->getMessage());

            return redirect()->route('subjects.index')->with('error', 'Unable to load subject. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Request $request, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find($id, $this->supabase);

            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $colors = Subject::getColorOptions();

            if ($request->header('HX-Request')) {
                return view('subjects.partials.edit-form', compact('subject', 'colors'));
            }

            return view('subjects.edit', compact('subject', 'colors'));
        } catch (\Exception $e) {
            Log::error('Error loading subject for edit: '.$e->getMessage());

            return response('Unable to load subject for editing.', 500);
        }
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find($id, $this->supabase);

            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'required|string',
            ]);

            // Validate color is in allowed options
            $allowedColors = array_keys(Subject::getColorOptions());
            if (! in_array($validated['color'], $allowedColors)) {
                return back()->withErrors(['color' => 'Invalid color selected.']);
            }

            $subject->name = $validated['name'];
            $subject->color = $validated['color'];

            if ($subject->save($this->supabase)) {
                if ($request->header('HX-Request')) {
                    // Return updated subjects list
                    $subjects = Subject::forUser($userId, $this->supabase);

                    return view('subjects.partials.subjects-list', compact('subjects'));
                }

                return redirect()->route('subjects.index')->with('success', 'Subject updated successfully.');
            } else {
                throw new \Exception('Failed to update subject');
            }
        } catch (\Exception $e) {
            Log::error('Error updating subject: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error updating subject. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to update subject. Please try again.']);
        }
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find($id, $this->supabase);

            if (! $subject || $subject->user_id !== $userId) {
                return response('Subject not found', 404);
            }

            // Check if subject has units - prevent deletion if it has units
            $unitCount = $subject->getUnitCount($this->supabase);
            if ($unitCount > 0) {
                if ($request->header('HX-Request')) {
                    return response('<div class="text-red-500">'.__('Cannot delete subject with existing units. Please delete all units first.').'</div>', 400);
                }

                return back()->withErrors(['error' => 'Cannot delete subject with existing units. Please delete all units first.']);
            }

            if ($subject->delete($this->supabase)) {
                if ($request->header('HX-Request')) {
                    // Return updated subjects list
                    $subjects = Subject::forUser($userId, $this->supabase);

                    return view('subjects.partials.subjects-list', compact('subjects'));
                }

                return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
            } else {
                throw new \Exception('Failed to delete subject');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting subject: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error deleting subject. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to delete subject. Please try again.']);
        }
    }

    /**
     * Show the quick start form for creating multiple subjects.
     */
    public function quickStartForm(Request $request)
    {
        $userId = session('user_id');
        if (! $userId) {
            return response('Unauthorized', 401);
        }

        // Get subject templates for all grade levels
        $templates = $this->getSubjectTemplates();

        if ($request->header('HX-Request')) {
            return view('subjects.partials.quick-start-modal', compact('templates'));
        }

        return response('This endpoint only supports HTMX requests', 400);
    }

    /**
     * Store multiple subjects from quick start.
     */
    public function quickStartStore(Request $request)
    {
        try {
            $userId = session('user_id');
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $validated = $request->validate([
                'grade_level' => 'required|in:elementary,middle,high',
                'subjects' => 'required|array|min:1',
                'subjects.*' => 'string|max:255',
                'custom_subjects' => 'nullable|array',
                'custom_subjects.*' => 'nullable|string|max:255',
            ]);

            $createdCount = 0;
            $colorOptions = array_keys(Subject::getColorOptions());
            $colorIndex = 0;

            // Create selected standard subjects
            foreach ($validated['subjects'] as $subjectName) {
                $subject = new Subject([
                    'name' => $subjectName,
                    'color' => $this->getSubjectColor($subjectName, $colorOptions, $colorIndex),
                    'user_id' => $userId,
                ]);

                if ($subject->save($this->supabase)) {
                    $createdCount++;
                    $colorIndex++;
                }
            }

            // Create custom subjects if any
            if (! empty($validated['custom_subjects'])) {
                foreach ($validated['custom_subjects'] as $customName) {
                    if (trim($customName) !== '') {
                        $subject = new Subject([
                            'name' => trim($customName),
                            'color' => $colorOptions[$colorIndex % count($colorOptions)],
                            'user_id' => $userId,
                        ]);

                        if ($subject->save($this->supabase)) {
                            $createdCount++;
                            $colorIndex++;
                        }
                    }
                }
            }

            if ($request->header('HX-Request')) {
                // Return updated subjects list
                $subjects = Subject::forUser($userId, $this->supabase);
                $showQuickStart = false;

                return view('subjects.partials.subjects-list', compact('subjects', 'showQuickStart'))
                    ->with('success', __(':count subjects created successfully', ['count' => $createdCount]));
            }

            return redirect()->route('subjects.index')
                ->with('success', __(':count subjects created successfully', ['count' => $createdCount]));

        } catch (\Exception $e) {
            Log::error('Error in quick start: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error creating subjects. Please try again.').'</div>', 500);
            }

            return back()->with('error', 'Unable to create subjects. Please try again.');
        }
    }

    /**
     * Get subject color based on subject type.
     */
    private function getSubjectColor(string $subjectName, array $colorOptions, int &$index): string
    {
        // Map subjects to specific colors for consistency
        $colorMap = [
            'Mathematics' => '#3B82F6', // Blue
            'Science' => '#10B981', // Green
            'Biology' => '#10B981',
            'Chemistry' => '#10B981',
            'Physics' => '#10B981',
            'Life Science' => '#10B981',
            'Earth Science' => '#10B981',
            'Physical Science' => '#10B981',
            'Reading/Language Arts' => '#8B5CF6', // Purple
            'English Language Arts' => '#8B5CF6',
            'Social Studies' => '#F97316', // Orange
            'History' => '#F97316',
            'World History' => '#F97316',
            'U.S. History' => '#F97316',
            'Art' => '#EC4899', // Pink
            'Music' => '#EC4899',
            'Physical Education' => '#EF4444', // Red
            'Health' => '#EF4444',
            'Computer Science' => '#6B7280', // Gray
            'Foreign Language' => '#14B8A6', // Teal
            'World Language' => '#14B8A6',
        ];

        // Check if we have a specific color for this subject
        foreach ($colorMap as $key => $color) {
            if (stripos($subjectName, $key) !== false) {
                return $color;
            }
        }

        // Return next available color from the options
        return $colorOptions[$index % count($colorOptions)];
    }

    /**
     * Get subject templates by grade level.
     */
    private function getSubjectTemplates(): array
    {
        return [
            'elementary' => [
                __('reading_language_arts'),
                __('mathematics'),
                __('science'),
                __('social_studies'),
                __('art'),
                __('music'),
                __('physical_education'),
            ],
            'middle' => [
                __('english_language_arts'),
                __('mathematics'),
                __('life_science'),
                __('earth_science'),
                __('physical_science'),
                __('social_studies'),
                __('world_history'),
                __('physical_education'),
                __('world_language'),
                __('computer_science'),
                __('art'),
                __('music'),
                __('health'),
            ],
            'high' => [
                __('english_language_arts'),
                __('algebra'),
                __('geometry'),
                __('calculus'),
                __('biology'),
                __('chemistry'),
                __('physics'),
                __('world_history'),
                __('us_history'),
                __('foreign_language'),
                __('computer_science'),
                __('economics'),
                __('psychology'),
                __('art'),
                __('physical_education'),
            ],
        ];
    }
}
