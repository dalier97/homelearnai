<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Review
{
    public ?int $id = null;

    public int $session_id;

    public int $child_id;

    public int $topic_id;

    public int $interval_days = 1; // SRS interval in days

    public float $ease_factor = 2.5; // SRS ease factor (1.3 - 2.5+)

    public int $repetitions = 0; // Number of times reviewed

    public string $status = 'new'; // new, learning, reviewing, mastered

    public ?Carbon $due_date = null; // When review is due

    public ?Carbon $last_reviewed_at = null;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    private const MAX_INTERVAL = 240; // Max 8 months

    private const MIN_EASE_FACTOR = 1.3;

    private const MAX_EASE_FACTOR = 2.5;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['due_date', 'last_reviewed_at', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(string $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('reviews')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('reviews')
            ->eq($column, $value)
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forChild(int $childId, SupabaseClient $supabase): Collection
    {
        return self::where('child_id', $childId, $supabase);
    }

    public static function forSession(int $sessionId, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('reviews')
            ->eq('session_id', $sessionId)
            ->single();

        return $data ? new self($data) : null;
    }

    /**
     * Get reviews due for today or overdue
     */
    public static function getDueReviews(int $childId, SupabaseClient $supabase, int $limit = 20): Collection
    {
        $today = Carbon::now()->format('Y-m-d');

        return $supabase->from('reviews')
            ->eq('child_id', $childId)
            ->lte('due_date', $today)
            ->neq('status', 'mastered')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => new self($item));
    }

    /**
     * Get new reviews (never reviewed)
     */
    public static function getNewReviews(int $childId, SupabaseClient $supabase, int $limit = 5): Collection
    {
        return $supabase->from('reviews')
            ->eq('child_id', $childId)
            ->eq('status', 'new')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => new self($item));
    }

    /**
     * Get mixed queue: new reviews + due reviews, properly balanced
     */
    public static function getReviewQueue(int $childId, SupabaseClient $supabase): Collection
    {
        $dueReviews = self::getDueReviews($childId, $supabase, 15);
        $newReviews = self::getNewReviews($childId, $supabase, 5);

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

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'session_id' => $this->session_id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'interval_days' => $this->interval_days,
            'ease_factor' => $this->ease_factor,
            'repetitions' => $this->repetitions,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('reviews')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('reviews')->insert($data);
            if ($result && isset($result[0]['id'])) {
                $this->id = $result[0]['id'];
                $this->created_at = Carbon::now();
            }
        }

        return ! empty($result);
    }

    public function delete(SupabaseClient $supabase): bool
    {
        if (! $this->id) {
            return false;
        }

        return $supabase->from('reviews')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Create a new review from a completed session
     */
    public static function createFromSession(Session $session, SupabaseClient $supabase): self
    {
        $review = new self([
            'session_id' => $session->id,
            'child_id' => $session->child_id,
            'topic_id' => $session->topic_id,
            'interval_days' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'status' => 'new',
            'due_date' => Carbon::now()->addDay(), // Due tomorrow
        ]);

        $review->save($supabase);

        return $review;
    }

    /**
     * Process review result and update SRS parameters
     */
    public function processResult(string $result, SupabaseClient $supabase): array
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

        $this->save($supabase);

        return [
            'old_interval' => $oldInterval,
            'new_interval' => $this->interval_days,
            'old_ease_factor' => round($oldEaseFactor, 2),
            'new_ease_factor' => round($this->ease_factor, 2),
            'next_due' => $this->due_date->format('M j, Y'),
            'status' => $this->status,
        ];
    }

    /**
     * Get related models
     */
    public function session(SupabaseClient $supabase): ?Session
    {
        return Session::find((string) $this->session_id, $supabase);
    }

    public function child(SupabaseClient $supabase): ?Child
    {
        return Child::find((int) $this->child_id);
    }

    public function topic(SupabaseClient $supabase): ?Topic
    {
        return Topic::find((string) $this->topic_id, $supabase);
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
            $weeks = round($this->interval_days / 7, 1);

            return $weeks.'w';
        } else {
            $months = round($this->interval_days / 30, 1);

            return $months.'mo';
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'interval_days' => $this->interval_days,
            'formatted_interval' => $this->getFormattedInterval(),
            'ease_factor' => round($this->ease_factor, 2),
            'repetitions' => $this->repetitions,
            'status' => $this->status,
            'status_color' => $this->getStatusColor(),
            'priority' => $this->getPriority(),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'formatted_due_date' => $this->due_date?->format('M j, Y'),
            'days_until_due' => $this->getDaysUntilDue(),
            'is_overdue' => $this->isOverdue(),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
