<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @property int $id
 * @property int $session_id
 * @property int $flashcard_id
 * @property int $child_id
 * @property int $topic_id
 * @property int $interval_days
 * @property float $ease_factor
 * @property int $repetitions
 * @property string $status
 * @property \Carbon\Carbon $due_date
 * @property \Carbon\Carbon|null $last_reviewed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Session $session
 * @property-read \App\Models\Flashcard $flashcard
 * @property-read \App\Models\Child $child
 * @property-read \App\Models\Topic $topic
 */
class Review extends Model
{
    protected $fillable = [
        'session_id',
        'flashcard_id',
        'child_id',
        'topic_id',
        'interval_days',
        'ease_factor',
        'repetitions',
        'status',
        'due_date',
        'last_reviewed_at',
    ];

    protected $casts = [
        'interval_days' => 'integer',
        'ease_factor' => 'decimal:2',
        'repetitions' => 'integer',
        'due_date' => 'date',
        'last_reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'interval_days' => 1,
        'ease_factor' => 2.5,
        'repetitions' => 0,
        'status' => 'new',
    ];

    private const MAX_INTERVAL = 240; // Max 8 months

    private const MIN_EASE_FACTOR = 1.3;

    private const MAX_EASE_FACTOR = 2.5;

    /**
     * Eloquent relationships
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Scopes and query methods
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review>
     */
    public static function forChild(int $childId): Collection
    {
        return self::where('child_id', $childId)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public static function forSession(int $sessionId): ?self
    {
        return self::where('session_id', $sessionId)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review>
     */
    public static function forFlashcard(int $flashcardId): Collection
    {
        return self::where('flashcard_id', $flashcardId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get reviews due for today or overdue
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review>
     */
    public static function getDueReviews(int $childId, int $limit = 20): Collection
    {
        $today = Carbon::now()->format('Y-m-d');

        return self::where('child_id', $childId)
            ->where('due_date', '<=', $today)
            ->where('status', '!=', 'mastered')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get new reviews (never reviewed)
     */
    public static function getNewReviews(int $childId, int $limit = 5): Collection
    {
        return self::where('child_id', $childId)
            ->where('status', 'new')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get mixed queue: new reviews + due reviews, properly balanced
     */
    public static function getReviewQueue(int $childId): BaseCollection
    {
        $dueReviews = self::getDueReviews($childId, 15);
        $newReviews = self::getNewReviews($childId, 5);

        // Interleave new and due reviews (3:1 ratio - 3 due, 1 new)
        $queue = collect([]);
        $dueIndex = 0;
        $newIndex = 0;

        while ($dueIndex < $dueReviews->count() || $newIndex < $newReviews->count()) {
            // Add 3 due reviews
            for ($i = 0; $i < 3 && $dueIndex < $dueReviews->count(); $i++) {
                $queue->push($dueReviews->get($dueIndex));
                $dueIndex++;
            }

            // Add 1 new review
            if ($newIndex < $newReviews->count()) {
                $queue->push($newReviews->get($newIndex));
                $newIndex++;
            }
        }

        return $queue->take(20); // Cap at 20 reviews per session
    }

    // Note: save() and delete() methods are now handled by Eloquent automatically

    /**
     * Create a new review from a completed session
     */
    public static function createFromSession(Session $session): self
    {
        return self::create([
            'session_id' => $session->id,
            'child_id' => $session->child_id,
            'topic_id' => $session->topic_id,
            'interval_days' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'status' => 'new',
            'due_date' => Carbon::now()->addDay(), // Due tomorrow
        ]);
    }

    /**
     * Create a new review from a flashcard
     */
    public static function createFromFlashcard(Flashcard $flashcard, int $childId): self
    {
        return self::create([
            'flashcard_id' => $flashcard->id,
            'child_id' => $childId,
            'topic_id' => $flashcard->unit->topics()->first()->id ?? 0, // Fallback to first topic in unit
            'interval_days' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'status' => 'new',
            'due_date' => Carbon::now()->addDay(), // Due tomorrow
        ]);
    }

    /**
     * Create reviews for all flashcards in a unit
     */
    public static function createForUnitFlashcards(int $unitId, int $childId): int
    {
        $flashcards = Flashcard::forUnit($unitId);
        $created = 0;

        foreach ($flashcards as $flashcard) {
            /** @var Flashcard $flashcard */
            // Check if review already exists for this flashcard and child
            $existingReview = self::where('flashcard_id', $flashcard->id)
                ->where('child_id', $childId)
                ->first();

            if (! $existingReview) {
                self::createFromFlashcard($flashcard, $childId);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Process review result and update SRS parameters
     */
    public function processResult(string $result): array
    {
        $this->last_reviewed_at = Carbon::now();
        $this->repetitions++;

        $oldInterval = $this->interval_days;
        $oldEaseFactor = $this->ease_factor;

        switch ($result) {
            case 'again': // Failed, start over
                $this->repetitions = 0;
                $this->interval_days = 1;
                $this->status = 'learning';
                $this->ease_factor = max(self::MIN_EASE_FACTOR, $this->ease_factor - 0.2);
                break;

            case 'hard': // Difficult but passed
                $this->interval_days = max(1, (int) ($this->interval_days * 1.2));
                $this->ease_factor = max(self::MIN_EASE_FACTOR, $this->ease_factor - 0.15);
                $this->status = $this->repetitions >= 2 ? 'reviewing' : 'learning';
                break;

            case 'good': // Normal difficulty
                if ($this->repetitions === 1) {
                    $this->interval_days = 3;
                } elseif ($this->repetitions === 2) {
                    $this->interval_days = 7;
                } else {
                    $this->interval_days = min(self::MAX_INTERVAL, (int) ($this->interval_days * $this->ease_factor));
                }
                $this->status = $this->repetitions >= 2 ? 'reviewing' : 'learning';
                break;

            case 'easy': // Very easy
                $this->interval_days = min(self::MAX_INTERVAL, (int) ($this->interval_days * $this->ease_factor * 1.3));
                $this->ease_factor = min(self::MAX_EASE_FACTOR, $this->ease_factor + 0.15);
                $this->status = $this->repetitions >= 2 ? 'reviewing' : 'learning';

                // Mark as mastered if interval is very long and was easy
                if ($this->interval_days >= 120 && $this->repetitions >= 4) {
                    $this->status = 'mastered';
                }
                break;
        }

        // Set next due date
        $this->due_date = Carbon::now()->addDays($this->interval_days);

        $this->save();

        return [
            'old_interval' => $oldInterval,
            'new_interval' => $this->interval_days,
            'old_ease_factor' => round((float) $oldEaseFactor, 2),
            'new_ease_factor' => round((float) $this->ease_factor, 2),
            'next_due' => $this->due_date->format('M j, Y'),
            'status' => $this->status,
        ];
    }

    // Relationships are now defined above using Eloquent methods

    /**
     * Check if this is a flashcard review
     */
    public function isFlashcardReview(): bool
    {
        return ! empty($this->flashcard_id);
    }

    /**
     * Check if this is a session/topic review
     */
    public function isTopicReview(): bool
    {
        return ! empty($this->session_id);
    }

    /**
     * Check if review is overdue
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDue(): int
    {
        if (! $this->due_date) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($this->due_date, false);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'new' => 'bg-blue-100 text-blue-800',
            'learning' => 'bg-yellow-100 text-yellow-800',
            'reviewing' => 'bg-green-100 text-green-800',
            'mastered' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get priority level for scheduling
     */
    public function getPriority(): int
    {
        if ($this->status === 'new') {
            return 3; // Medium priority
        }

        $daysUntilDue = $this->getDaysUntilDue();

        if ($daysUntilDue < -7) {
            return 1; // Critical - very overdue
        } elseif ($daysUntilDue < 0) {
            return 2; // High - overdue
        } elseif ($daysUntilDue <= 1) {
            return 2; // High - due soon
        } else {
            return 3; // Medium - future
        }
    }

    /**
     * Get formatted interval for display
     */
    public function getFormattedInterval(): string
    {
        if ($this->interval_days < 7) {
            return $this->interval_days.'d';
        } elseif ($this->interval_days < 30) {
            $weeks = round((float) $this->interval_days / 7, 1);

            return $weeks.'w';
        } else {
            $months = round((float) $this->interval_days / 30, 1);

            return $months.'mo';
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'flashcard_id' => $this->flashcard_id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'interval_days' => $this->interval_days,
            'formatted_interval' => $this->getFormattedInterval(),
            'ease_factor' => round((float) $this->ease_factor, 2),
            'repetitions' => $this->repetitions,
            'status' => $this->status,
            'status_color' => $this->getStatusColor(),
            'priority' => $this->getPriority(),
            'due_date' => $this->due_date->format('Y-m-d'),
            'formatted_due_date' => $this->due_date->format('M j, Y'),
            'days_until_due' => $this->getDaysUntilDue(),
            'is_overdue' => $this->isOverdue(),
            'is_flashcard_review' => $this->isFlashcardReview(),
            'is_topic_review' => $this->isTopicReview(),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
