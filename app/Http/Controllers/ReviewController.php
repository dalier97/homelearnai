<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Flashcard;
use App\Models\Review;
use App\Models\ReviewSlot;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Show review dashboard
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();
        $children = Child::where('user_id', $userId)->orderBy('name')->get();

        // Default to first child or selected child
        $selectedChildId = $request->get('child_id', $children->first()?->id);
        $selectedChild = $selectedChildId ? Child::find((int) $selectedChildId) : null;

        if (! $selectedChild) {
            return view('reviews.index', [
                'children' => $children,
                'selectedChild' => null,
                'reviewQueue' => collect([]),
                'reviewStats' => [],
                'todaySlots' => collect([]),
            ]);
        }

        // Get review queue for today
        $reviewQueue = Review::getReviewQueue($selectedChild->id);

        // Get review statistics
        $reviewStats = $this->getReviewStats($selectedChild->id);

        // Get today's review slots
        $todaySlots = ReviewSlot::getTodaySlots($selectedChild->id);

        // Get all slots for showing next scheduled review
        $allSlots = ReviewSlot::forChild($selectedChild->id);

        // If HTMX request, return partial
        if ($request->header('HX-Request')) {
            return view('reviews.partials.dashboard', compact(
                'selectedChild', 'reviewQueue', 'reviewStats', 'todaySlots', 'allSlots'
            ));
        }

        return view('reviews.index', compact(
            'children', 'selectedChild', 'reviewQueue', 'reviewStats', 'todaySlots', 'allSlots'
        ));
    }

    /**
     * Start a review session
     */
    public function startSession(Request $request, int $childId): View|RedirectResponse
    {
        $userId = auth()->id();

        $child = Child::find((int) $childId);
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $reviewQueue = Review::getReviewQueue($childId);

        if ($reviewQueue->isEmpty()) {
            return redirect()->route('reviews.index', ['child_id' => $childId])
                ->with('message', 'No reviews available right now!');
        }

        $currentReview = $reviewQueue->first();

        return view('reviews.session', compact('child', 'currentReview', 'reviewQueue'));
    }

    /**
     * Process review result
     */
    public function processResult(Request $request, int $reviewId): JsonResponse
    {
        $validated = $request->validate([
            'result' => 'required|string|in:again,hard,good,easy',
        ]);

        $userId = auth()->id();

        $review = Review::find($reviewId);
        if (! $review) {
            abort(404);
        }

        // Verify review belongs to user's child
        $child = $review->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $result = $review->processResult($validated['result']);

        // Get next review in queue
        $reviewQueue = Review::getReviewQueue($child->id);
        $nextReview = $reviewQueue->skip(1)->first(); // Skip the current one we just processed

        // Prepare next review data
        $nextReviewData = null;
        if ($nextReview) {
            $nextReviewData = [
                'id' => $nextReview->id,
                'status' => $nextReview->status,
                'repetitions' => $nextReview->repetitions,
                'is_flashcard_review' => $nextReview->isFlashcardReview(),
                'is_topic_review' => $nextReview->isTopicReview(),
            ];

            if ($nextReview->isFlashcardReview()) {
                $flashcard = $nextReview->flashcard();
                $nextReviewData['flashcard_title'] = $flashcard ? $flashcard->question : 'Unknown Flashcard';
                $nextReviewData['card_type'] = $flashcard ? $flashcard->card_type : 'basic';
                $nextReviewData['unit_name'] = $flashcard && $flashcard->unit ? $flashcard->unit->name : 'Unknown Unit';
            } else {
                $nextReviewData['topic_title'] = $nextReview->topic->title;
            }
        }

        return response()->json([
            'success' => true,
            'result' => $result,
            'next_review' => $nextReviewData,
            'session_complete' => $nextReview === null,
            'review_type' => $review->isFlashcardReview() ? 'flashcard' : 'topic',
        ]);
    }

    /**
     * Process flashcard review result with answer validation
     */
    public function processFlashcardResult(Request $request, int $reviewId): JsonResponse
    {
        $validated = $request->validate([
            'result' => 'required|string|in:again,hard,good,easy',
            'user_answer' => 'nullable|string',
            'selected_choices' => 'nullable|array',
            'cloze_answers' => 'nullable|array',
            'is_correct' => 'nullable|boolean',
        ]);

        $userId = auth()->id();

        $review = Review::find($reviewId);
        if (! $review || ! $review->isFlashcardReview()) {
            abort(404, 'Flashcard review not found');
        }

        // Verify review belongs to user's child
        $child = $review->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $flashcard = $review->flashcard();
        /** @var \App\Models\Flashcard $flashcard */
        if (! $flashcard) {
            abort(404, 'Associated flashcard not found');
        }

        // Validate the answer based on card type
        $answerValidation = $this->validateFlashcardAnswer($flashcard, $validated);

        // Adjust result based on answer correctness if not explicitly provided
        if (! isset($validated['is_correct']) && isset($answerValidation['is_correct'])) {
            $validated['is_correct'] = $answerValidation['is_correct'];

            // Auto-adjust rating for incorrect answers
            if (! $answerValidation['is_correct'] && in_array($validated['result'], ['good', 'easy'])) {
                $validated['result'] = 'again'; // Force retry for incorrect answers rated as good/easy
            }
        }

        // Process the review with SRS algorithm
        $result = $review->processResult($validated['result']);

        // Add answer validation info to result
        $result['answer_validation'] = $answerValidation;

        // Get next review in queue
        $reviewQueue = Review::getReviewQueue($child->id);
        $nextReview = $reviewQueue->skip(1)->first();

        // Prepare next review data
        $nextReviewData = null;
        if ($nextReview) {
            $nextReviewData = [
                'id' => $nextReview->id,
                'status' => $nextReview->status,
                'repetitions' => $nextReview->repetitions,
                'is_flashcard_review' => $nextReview->isFlashcardReview(),
                'is_topic_review' => $nextReview->isTopicReview(),
            ];

            if ($nextReview->isFlashcardReview()) {
                $nextFlashcard = $nextReview->flashcard();
                $nextReviewData['flashcard_title'] = $nextFlashcard ? $nextFlashcard->question : 'Unknown Flashcard';
                $nextReviewData['card_type'] = $nextFlashcard ? $nextFlashcard->card_type : 'basic';
                $nextReviewData['unit_name'] = $nextFlashcard && $nextFlashcard->unit ? $nextFlashcard->unit->name : 'Unknown Unit';
            } else {
                $nextReviewData['topic_title'] = $nextReview->topic->title;
            }
        }

        return response()->json([
            'success' => true,
            'result' => $result,
            'next_review' => $nextReviewData,
            'session_complete' => $nextReview === null,
            'review_type' => 'flashcard',
        ]);
    }

    /**
     * Validate flashcard answer based on card type
     */
    private function validateFlashcardAnswer(Flashcard $flashcard, array $input): array
    {
        $validation = [
            'is_correct' => false,
            'correct_answer' => $flashcard->answer,
            'user_answer' => null,
            'feedback' => '',
        ];

        switch ($flashcard->card_type) {
            case 'multiple_choice':
            case 'true_false':
                if (isset($input['selected_choices']) && is_array($input['selected_choices'])) {
                    $selectedChoices = $input['selected_choices'];
                    $correctChoices = $flashcard->correct_choices ?? [];

                    // Check if selected choices match correct choices
                    $validation['is_correct'] =
                        count($selectedChoices) === count($correctChoices) &&
                        count(array_diff($selectedChoices, $correctChoices)) === 0;

                    $validation['user_answer'] = $selectedChoices;
                }
                break;

            case 'typed_answer':
                if (isset($input['user_answer'])) {
                    $userAnswer = trim($input['user_answer']);
                    $correctAnswer = trim($flashcard->answer);

                    // Case-insensitive comparison
                    $validation['is_correct'] =
                        strtolower($userAnswer) === strtolower($correctAnswer);

                    $validation['user_answer'] = $userAnswer;
                }
                break;

            case 'cloze':
                if (isset($input['cloze_answers']) && is_array($input['cloze_answers'])) {
                    $userAnswers = $input['cloze_answers'];
                    $correctAnswers = $flashcard->cloze_answers ?? [];

                    $allCorrect = true;
                    foreach ($correctAnswers as $index => $correctAnswer) {
                        $userAnswer = isset($userAnswers[$index]) ? trim($userAnswers[$index]) : '';
                        if (strtolower($userAnswer) !== strtolower(trim($correctAnswer))) {
                            $allCorrect = false;
                            break;
                        }
                    }

                    $validation['is_correct'] = $allCorrect;
                    $validation['user_answer'] = $userAnswers;
                }
                break;

            case 'basic':
            case 'image_occlusion':
            default:
                // For basic cards and image occlusion, rely on user self-assessment
                $validation['is_correct'] = $input['is_correct'] ?? null;
                $validation['user_answer'] = $input['user_answer'] ?? 'self-assessed';
                break;
        }

        return $validation;
    }

    /**
     * Show review details for a specific review
     */
    public function show(int $reviewId): View
    {
        $userId = auth()->id();

        $review = Review::find($reviewId);
        if (! $review) {
            abort(404);
        }

        // Verify review belongs to user's child
        $child = $review->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $session = $review->session;
        $topic = $review->topic;

        return view('reviews.partials.review-card', compact('review', 'session', 'topic', 'child'));
    }

    /**
     * Complete session with evidence capture
     */
    public function completeSession(Request $request, int $sessionId): View|RedirectResponse
    {
        $validated = $request->validate([
            'evidence_notes' => 'nullable|string|max:2000',
            'evidence_photos' => 'nullable|array|max:5',
            'evidence_photos.*' => 'file|image|max:5120', // 5MB max per image
            'evidence_voice_memo' => 'nullable|file|mimes:mp3,wav,m4a,ogg|max:10240', // 10MB max
            'evidence_attachments' => 'nullable|array|max:3',
            'evidence_attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        $userId = auth()->id();

        $session = Session::find($sessionId);
        if (! $session) {
            abort(404);
        }

        // Verify session belongs to user's child
        $child = $session->child;
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        // Handle file uploads
        $photoUrls = [];
        if (isset($validated['evidence_photos'])) {
            foreach ($validated['evidence_photos'] as $photo) {
                $photoUrls[] = $this->storeEvidenceFile($photo, $child->id, $session->id, 'photos');
            }
        }

        $voiceMemoUrl = null;
        if (isset($validated['evidence_voice_memo'])) {
            $voiceMemoUrl = $this->storeEvidenceFile($validated['evidence_voice_memo'], $child->id, $session->id, 'voice');
        }

        $attachmentUrls = [];
        if (isset($validated['evidence_attachments'])) {
            foreach ($validated['evidence_attachments'] as $attachment) {
                $attachmentUrls[] = $this->storeEvidenceFile($attachment, $child->id, $session->id, 'attachments');
            }
        }

        // Complete session with evidence
        $session->completeWithEvidence(
            $validated['evidence_notes'] ?? null,
            ! empty($photoUrls) ? $photoUrls : null,
            $voiceMemoUrl,
            ! empty($attachmentUrls) ? $attachmentUrls : null
        );

        // Return success response for HTMX
        if ($request->header('HX-Request')) {
            return view('reviews.partials.completion-success', compact('session', 'child'))
                ->with('htmx_trigger', 'sessionCompleted');
        }

        return redirect()->route('reviews.index', ['child_id' => $child->id])
            ->with('message', 'Session completed with evidence captured!');
    }

    /**
     * Manage review slots for a child
     */
    public function manageSlots(Request $request, int $childId): View
    {
        $userId = auth()->id();

        $child = Child::find((int) $childId);
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $reviewSlots = ReviewSlot::forChild($childId);
        $weeklySlots = [];

        // Organize slots by day of week
        foreach (range(1, 7) as $day) {
            $weeklySlots[$day] = $reviewSlots->where('day_of_week', $day)->sortBy('start_time');
        }

        if ($request->header('HX-Request')) {
            return view('reviews.partials.slots-manager', compact('child', 'weeklySlots'));
        }

        return view('reviews.slots', compact('child', 'weeklySlots'));
    }

    /**
     * Store review slot
     */
    public function storeSlot(Request $request)
    {
        \Log::info('storeSlot called', ['method' => $request->method(), 'data' => $request->all()]);
        try {
            $validated = $request->validate([
                'child_id' => 'required|integer',
                'day_of_week' => 'required|integer|min:1|max:7',
                'start_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'end_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                'slot_type' => 'required|string|in:micro,standard',
            ]);

            // Additional validation for end_time after start_time
            if ($validated['end_time'] <= $validated['start_time']) {
                if ($request->header('HX-Request')) {
                    return response()->view('reviews.partials.slots-manager', [
                        'error' => 'End time must be after start time.',
                        'weeklySlots' => [],
                        'child' => null,
                    ], 422);
                }

                return back()->withErrors(['end_time' => 'End time must be after start time.']);
            }

            $userId = auth()->id();

            // Verify child belongs to the current user
            try {
                $child = Child::find((int) $validated['child_id']);
                if (! $child || $child->user_id !== $userId) {
                    abort(403);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to find child: '.$e->getMessage());
                if ($request->header('HX-Request')) {
                    return response()->view('reviews.partials.slots-manager', [
                        'error' => 'Child not found.',
                        'weeklySlots' => [],
                        'child' => null,
                    ], 404);
                }

                return back()->withErrors(['child_id' => 'Child not found.']);
            }

            // Check for overlapping slots
            try {
                $existingSlots = ReviewSlot::forChildAndDay($validated['child_id'], $validated['day_of_week']);
                $newStartTime = $validated['start_time'].':00';
                $newEndTime = $validated['end_time'].':00';

                foreach ($existingSlots as $slot) {
                    $existingStart = $slot->start_time;
                    $existingEnd = $slot->end_time;

                    // Check if times overlap
                    if (($newStartTime >= $existingStart && $newStartTime < $existingEnd) ||
                        ($newEndTime > $existingStart && $newEndTime <= $existingEnd) ||
                        ($newStartTime <= $existingStart && $newEndTime >= $existingEnd)) {

                        if ($request->header('HX-Request')) {
                            return response()->view('reviews.partials.slots-manager', [
                                'error' => 'Time slots cannot overlap with existing slots.',
                                'weeklySlots' => [],
                                'child' => $child,
                            ], 422);
                        }

                        return back()->withErrors(['start_time' => 'Time slots cannot overlap with existing slots.']);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to check existing slots: '.$e->getMessage());
                // Continue with slot creation
            }

            // Create the review slot
            $reviewSlot = new ReviewSlot([
                'child_id' => $validated['child_id'],
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'slot_type' => $validated['slot_type'],
                'is_active' => true,
            ]);

            try {
                $success = $reviewSlot->save();

                if (! $success) {
                    if ($request->header('HX-Request')) {
                        return response()->view('reviews.partials.slots-manager', [
                            'error' => 'Failed to save review slot. Please try again.',
                            'weeklySlots' => [],
                            'child' => $child,
                        ], 500);
                    }

                    return back()->withErrors(['general' => 'Failed to save review slot. Please try again.']);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to save review slot: '.$e->getMessage());
                if ($request->header('HX-Request')) {
                    return response()->view('reviews.partials.slots-manager', [
                        'error' => 'Failed to save review slot: '.$e->getMessage(),
                        'weeklySlots' => [],
                        'child' => $child,
                    ], 500);
                }

                return back()->withErrors(['general' => 'Failed to save review slot: '.$e->getMessage()]);
            }

            // Return different responses for HTMX vs regular requests
            if ($request->header('HX-Request')) {
                // Get fresh weekly slots for HTMX response
                $weeklySlots = [];
                foreach (range(1, 7) as $day) {
                    $weeklySlots[$day] = ReviewSlot::forChildAndDay($validated['child_id'], $day);
                }

                return response()->view('reviews.partials.slots-manager', [
                    'weeklySlots' => $weeklySlots,
                    'child' => $child,
                ])->header('HX-Trigger', 'reviewSlotCreated');
            }

            return redirect()->route('reviews.slots', $child->id)
                ->with('message', 'Review slot created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->header('HX-Request')) {
                return response()->view('reviews.partials.slots-manager', [
                    'error' => 'Validation failed: '.implode(' ', collect($e->errors())->flatten()->toArray()),
                    'weeklySlots' => [],
                    'child' => null,
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Unexpected error in storeSlot: '.$e->getMessage());
            if ($request->header('HX-Request')) {
                return response()->view('reviews.partials.slots-manager', [
                    'error' => 'An unexpected error occurred. Please try again.',
                    'weeklySlots' => [],
                    'child' => null,
                ], 500);
            }

            return back()->withErrors(['general' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Update review slot
     */
    public function updateSlot(Request $request, int $id): View
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'end_time' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/|after:start_time',
            'slot_type' => 'required|string|in:micro,standard',
        ]);

        $userId = auth()->id();

        $reviewSlot = ReviewSlot::find($id);
        if (! $reviewSlot) {
            abort(404);
        }

        // Verify slot belongs to user's child
        $child = $reviewSlot->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        foreach ($validated as $key => $value) {
            if ($key === 'start_time' || $key === 'end_time') {
                $reviewSlot->$key = $value.':00';
            } else {
                $reviewSlot->$key = $value;
            }
        }

        $reviewSlot->save();

        // Return updated slots for the specific day
        $slotsForDay = ReviewSlot::forChildAndDay($reviewSlot->child_id, $validated['day_of_week']);

        return view('reviews.partials.day-slots', [
            'day' => $validated['day_of_week'],
            'slots' => $slotsForDay,
            'child' => $child,
        ])->with('htmx_trigger', 'reviewSlotUpdated');
    }

    /**
     * Delete review slot
     */
    public function destroySlot(int $id): string
    {
        $userId = auth()->id();

        $reviewSlot = ReviewSlot::find($id);
        if (! $reviewSlot) {
            abort(404);
        }

        // Verify slot belongs to user's child
        $child = $reviewSlot->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $day = $reviewSlot->day_of_week;
        $childId = $reviewSlot->child_id;

        $reviewSlot->delete();

        // Return empty response for HTMX to remove element
        response()->noContent()->header('HX-Trigger', 'reviewSlotDeleted')->send();

        return '';
    }

    /**
     * Toggle review slot active status
     */
    public function toggleSlot(int $id): View
    {
        $userId = auth()->id();

        $reviewSlot = ReviewSlot::find($id);
        if (! $reviewSlot) {
            abort(404);
        }

        // Verify slot belongs to user's child
        $child = $reviewSlot->child;
        /** @var \App\Models\Child $child */
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        $reviewSlot->toggleActive();

        // Return updated slots for the specific day
        $slotsForDay = ReviewSlot::forChildAndDay($reviewSlot->child_id, $reviewSlot->day_of_week);

        return view('reviews.partials.day-slots', [
            'day' => $reviewSlot->day_of_week,
            'slots' => $slotsForDay,
            'child' => $child,
        ])->with('htmx_trigger', 'reviewSlotToggled');
    }

    /**
     * Get review statistics for a child
     */
    private function getReviewStats(int $childId): array
    {
        $allReviews = Review::forChild($childId);
        $dueReviews = Review::getDueReviews($childId, 100);
        $newReviews = Review::getNewReviews($childId, 100);

        $statusCounts = $allReviews->groupBy('status')->map->count();

        // Calculate recent performance metrics (last 30 days)
        $recentReviews = $allReviews->where('last_reviewed_at', '>=', now()->subDays(30));
        $performanceCounts = $this->calculatePerformanceBreakdown($recentReviews);

        // Calculate weekly stats (last 7 days)
        $weeklyReviews = $allReviews->where('last_reviewed_at', '>=', now()->subDays(7));
        $weeklyStats = $this->calculateWeeklyStats($weeklyReviews);

        // Calculate monthly stats (last 30 days)
        $monthlyReviews = $allReviews->where('last_reviewed_at', '>=', now()->subDays(30));
        $monthlyStats = $this->calculateMonthlyStats($monthlyReviews);

        return array_merge([
            'total_reviews' => $allReviews->count(),
            'due_today' => $dueReviews->count(),
            'new_cards' => $newReviews->count(),
            'overdue' => $dueReviews->where('is_overdue', true)->count(),
            'mastered' => $statusCounts['mastered'] ?? 0,
            'learning' => $statusCounts['learning'] ?? 0,
            'reviewing' => $statusCounts['reviewing'] ?? 0,
            'retention_rate' => $this->calculateRetentionRate($allReviews),
        ], $performanceCounts, $weeklyStats, $monthlyStats);
    }

    /**
     * Calculate retention rate based on review performance
     */
    private function calculateRetentionRate($reviews): float
    {
        $reviewsWithHistory = $reviews->where('repetitions', '>', 0);

        if ($reviewsWithHistory->isEmpty()) {
            return 0.0;
        }

        // Calculate based on reviews that are not in "again" state
        $successful = $reviewsWithHistory->where('status', '!=', 'learning')->count();

        return round(($successful / $reviewsWithHistory->count()) * 100, 1);
    }

    /**
     * Calculate performance breakdown by difficulty
     */
    private function calculatePerformanceBreakdown($reviews): array
    {
        // This would need review history data to work properly
        // For now, return zeros since there's no real data
        return [
            'performance_again' => 0,
            'performance_hard' => 0,
            'performance_good' => 0,
            'performance_easy' => 0,
        ];
    }

    /**
     * Calculate weekly statistics
     */
    private function calculateWeeklyStats($weeklyReviews): array
    {
        $totalReviews = $weeklyReviews->count();
        $successfulReviews = $weeklyReviews->where('status', '!=', 'learning')->count();
        $avgInterval = $weeklyReviews->avg('interval_days') ?? 0;
        $newReviews = $weeklyReviews->where('repetitions', 0)->count();

        return [
            'weekly_reviews' => $totalReviews,
            'weekly_success' => $totalReviews > 0 ? round(($successfulReviews / $totalReviews) * 100, 1) : 0,
            'weekly_avg_days' => round($avgInterval, 1),
            'weekly_new' => $newReviews,
        ];
    }

    /**
     * Calculate monthly statistics
     */
    private function calculateMonthlyStats($monthlyReviews): array
    {
        $totalReviews = $monthlyReviews->count();
        $successfulReviews = $monthlyReviews->where('status', '!=', 'learning')->count();
        $avgInterval = $monthlyReviews->avg('interval_days') ?? 0;
        $newReviews = $monthlyReviews->where('repetitions', 0)->count();

        return [
            'monthly_reviews' => $totalReviews,
            'monthly_success' => $totalReviews > 0 ? round(($successfulReviews / $totalReviews) * 100, 1) : 0,
            'monthly_avg_days' => round($avgInterval, 1),
            'monthly_new' => $newReviews,
        ];
    }

    /**
     * Store evidence file and return storage path
     */
    private function storeEvidenceFile(UploadedFile $file, int $childId, int $sessionId, string $type): string
    {
        $directory = "evidence/{$childId}/{$sessionId}/{$type}";
        $filename = time().'_'.$file->getClientOriginalName();

        // Store in storage/app/public/evidence structure
        $path = $file->storeAs("public/{$directory}", $filename);

        // Return the public URL path
        return "storage/{$directory}/{$filename}";
    }
}
