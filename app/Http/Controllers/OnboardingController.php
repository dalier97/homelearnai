<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OnboardingController extends Controller
{
    /**
     * Display the onboarding wizard
     */
    public function index()
    {
        return view('onboarding.index');
    }

    /**
     * Handle AJAX submission of children data (Phase 3)
     */
    public function storeChild(Request $request)
    {
        $validated = $request->validate([
            'children' => 'required|array|min:1|max:5',
            'children.*.name' => 'required|string|max:255',
            'children.*.grade' => 'required|string|in:PreK,K,1st,2nd,3rd,4th,5th,6th,7th,8th,9th,10th,11th,12th',
            'children.*.independence_level' => 'required|integer|in:1,2,3,4',
        ]);

        try {
            // Get authenticated user
            $user = Auth::user();
            if (! $user) {
                \Log::error('Onboarding: User not authenticated', [
                    'session_data' => Session::all(),
                    'session_id' => Session::getId(),
                ]);

                return response()->json(['error' => __('User not authenticated')], 401);
            }

            $childrenCreated = [];

            // Create each child using Laravel native models
            foreach ($validated['children'] as $childData) {
                $child = Child::create([
                    'user_id' => $user->id,
                    'name' => $childData['name'],
                    'grade' => $childData['grade'],
                    'independence_level' => $childData['independence_level'],
                ]);

                $childrenCreated[] = $child;
            }

            // Store children IDs in session for use in next steps
            Session::put('onboarding_children_ids', collect($childrenCreated)->pluck('id')->toArray());

            \Log::info('Onboarding: Children created successfully', [
                'user_id' => $user->id,
                'children_count' => count($childrenCreated),
                'children_ids' => collect($childrenCreated)->pluck('id')->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Children added successfully'),
                'children' => $childrenCreated,
            ]);

        } catch (\Exception $e) {
            \Log::error('Onboarding: Failed to save children', [
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => __('Failed to save children. Please try again.')], 500);
        }
    }

    /**
     * Return children data for the onboarding form
     */
    public function children(Request $request)
    {
        // Return empty array as children will be created during onboarding
        // This endpoint might be used to load existing children if onboarding is resumed
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $children = $user->children()->get(['id', 'name', 'grade', 'independence_level']);

        return response()->json([
            'success' => true,
            'children' => $children,
        ]);
    }

    /**
     * Return available subjects for the onboarding form
     */
    public function subjects(Request $request)
    {
        // Return predefined subjects that users can select from during onboarding
        $predefinedSubjects = [
            ['name' => 'Mathematics', 'color' => '#ef4444'],
            ['name' => 'Science', 'color' => '#10b981'],
            ['name' => 'Language Arts', 'color' => '#3b82f6'],
            ['name' => 'History', 'color' => '#8b5cf6'],
            ['name' => 'Art', 'color' => '#f59e0b'],
            ['name' => 'Music', 'color' => '#ec4899'],
            ['name' => 'Physical Education', 'color' => '#06b6d4'],
            ['name' => 'Foreign Language', 'color' => '#84cc16'],
        ];

        return response()->json([
            'success' => true,
            'subjects' => $predefinedSubjects,
        ]);
    }

    /**
     * Handle AJAX submission of subjects (Phase 4)
     */
    public function storeSubjects(Request $request)
    {
        $validated = $request->validate([
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:255',
            'subjects.*.child_id' => 'required|integer',
            'subjects.*.color' => 'required|string',
        ]);

        try {
            // Get authenticated user
            $user = Auth::user();
            if (! $user) {
                return response()->json(['error' => __('User not authenticated')], 401);
            }

            $subjectsCreated = [];

            // Create each subject using Laravel native models
            foreach ($validated['subjects'] as $subjectData) {
                // Verify the child belongs to the user using Eloquent
                $child = Child::where('id', $subjectData['child_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (! $child) {
                    return response()->json(['error' => __('Invalid child selected')], 400);
                }

                $subject = Subject::create([
                    'user_id' => $user->id,
                    'child_id' => $subjectData['child_id'],
                    'name' => $subjectData['name'],
                    'color' => $subjectData['color'],
                ]);

                $subjectsCreated[] = $subject;
            }

            // Store subjects count in session for completion message
            $totalSubjects = count($subjectsCreated);
            $message = $totalSubjects === 1
                ? __('1 subject created successfully')
                : __(':count subjects created successfully', ['count' => $totalSubjects]);

            \Log::info('Onboarding: Subjects created successfully', [
                'user_id' => $user->id,
                'subjects_count' => count($subjectsCreated),
                'subjects_ids' => collect($subjectsCreated)->pluck('id')->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'subjects' => $subjectsCreated,
            ]);

        } catch (\Exception $e) {
            \Log::error('Onboarding: Failed to save subjects', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => __('Failed to save subjects')], 500);
        }
    }

    /**
     * Finalize onboarding (Phase 5)
     */
    public function complete(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            if (! $user) {
                return response()->json(['error' => __('User not authenticated')], 401);
            }

            // Update user preferences using Eloquent for better performance
            $userPrefs = $user->getPreferences();
            $userPrefs->update([
                'onboarding_completed' => true,
                'locale' => session('locale', 'en'),
            ]);

            // Store in session for immediate use
            Session::put('onboarding_completed', true);

            \Log::info('Onboarding: Onboarding completed successfully', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Onboarding completed successfully!'),
                'redirect' => route('dashboard'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Onboarding: Failed to complete onboarding', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['error' => __('Failed to complete onboarding')], 500);
        }
    }

    /**
     * Skip the onboarding wizard and go to dashboard
     */
    public function skip(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            if (! $user) {
                return redirect()->route('login')->withErrors(['error' => __('User not authenticated')]);
            }

            // Mark onboarding as completed (skipped) using Eloquent for better performance
            $userPrefs = $user->getPreferences();
            $userPrefs->update([
                'onboarding_completed' => true,
                'onboarding_skipped' => true,
                'locale' => session('locale', 'en'),
            ]);

            // Store in session for immediate use
            Session::put('onboarding_completed', true);

            \Log::info('Onboarding: Onboarding skipped successfully', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('dashboard')->with('info', __('You can set up your homeschool environment using the navigation menu.'));

        } catch (\Exception $e) {
            \Log::error('Onboarding: Failed to skip onboarding', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('onboarding.index')->withErrors(['error' => __('Failed to skip onboarding')]);
        }
    }
}
