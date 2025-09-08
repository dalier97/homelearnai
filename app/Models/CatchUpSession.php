<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CatchUpSession
{
    public ?int $id = null;

    public int $original_session_id;

    public int $child_id;

    public int $topic_id;

    public int $estimated_minutes;

    public int $priority = 1; // 1=highest, 5=lowest

    public Carbon $missed_date;

    public ?string $reason = null;

    public ?int $reassigned_to_session_id = null;

    public string $status = 'pending'; // pending, reassigned, completed, cancelled

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['missed_date', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(string $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('catch_up_sessions')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('catch_up_sessions')
            ->eq($column, $value)
            ->orderBy('priority', 'asc')
            ->orderBy('missed_date', 'desc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forChild(int $childId, SupabaseClient $supabase): Collection
    {
        return self::where('child_id', $childId, $supabase);
    }

    public static function forChildAndStatus(int $childId, string $status, SupabaseClient $supabase): Collection
    {
        return $supabase->from('catch_up_sessions')
            ->eq('child_id', $childId)
            ->eq('status', $status)
            ->orderBy('priority', 'asc')
            ->orderBy('missed_date', 'desc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function pending(int $childId, SupabaseClient $supabase): Collection
    {
        return self::forChildAndStatus($childId, 'pending', $supabase);
    }

    public static function byPriority(int $childId, int $priority, SupabaseClient $supabase): Collection
    {
        return $supabase->from('catch_up_sessions')
            ->eq('child_id', $childId)
            ->eq('priority', $priority)
            ->orderBy('missed_date', 'desc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'original_session_id' => $this->original_session_id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'estimated_minutes' => $this->estimated_minutes,
            'priority' => $this->priority,
            'missed_date' => $this->missed_date->format('Y-m-d'),
            'reason' => $this->reason,
            'reassigned_to_session_id' => $this->reassigned_to_session_id,
            'status' => $this->status,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('catch_up_sessions')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('catch_up_sessions')->insert($data);
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

        return $supabase->from('catch_up_sessions')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get the original session this catch-up is for
     */
    public function originalSession(SupabaseClient $supabase): ?Session
    {
        return Session::find((string) $this->original_session_id, $supabase);
    }

    /**
     * Get the session this catch-up was reassigned to
     */
    public function reassignedToSession(SupabaseClient $supabase): ?Session
    {
        if (! $this->reassigned_to_session_id) {
            return null;
        }

        return Session::find((string) $this->reassigned_to_session_id, $supabase);
    }

    /**
     * Get the topic this catch-up session is for
     */
    public function topic(SupabaseClient $supabase): ?Topic
    {
        return Topic::find((string) $this->topic_id, $supabase);
    }

    /**
     * Get the child this catch-up session is for
     */
    public function child(SupabaseClient $supabase): ?Child
    {
        return Child::find((string) $this->child_id, $supabase);
    }

    /**
     * Get the unit this catch-up belongs to (through topic)
     */
    public function unit(SupabaseClient $supabase): ?Unit
    {
        $topic = $this->topic($supabase);

        return $topic ? $topic->unit($supabase) : null;
    }

    /**
     * Get the subject this catch-up belongs to (through topic -> unit)
     */
    public function subject(SupabaseClient $supabase): ?Subject
    {
        $topic = $this->topic($supabase);

        return $topic ? $topic->subject($supabase) : null;
    }

    /**
     * Mark as reassigned to a specific session
     */
    public function reassignToSession(int $sessionId, SupabaseClient $supabase): bool
    {
        $this->reassigned_to_session_id = $sessionId;
        $this->status = 'reassigned';

        return $this->save($supabase);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(SupabaseClient $supabase): bool
    {
        $this->status = 'completed';

        return $this->save($supabase);
    }

    /**
     * Mark as cancelled
     */
    public function markCancelled(string $reason, SupabaseClient $supabase): bool
    {
        $this->status = 'cancelled';
        $this->reason = $reason;

        return $this->save($supabase);
    }

    /**
     * Update priority (1=highest, 5=lowest)
     */
    public function updatePriority(int $priority, SupabaseClient $supabase): bool
    {
        if ($priority < 1 || $priority > 5) {
            throw new \InvalidArgumentException('Priority must be between 1 and 5');
        }

        $this->priority = $priority;

        return $this->save($supabase);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        if ($this->estimated_minutes < 60) {
            return "{$this->estimated_minutes}m";
        }

        $hours = floor($this->estimated_minutes / 60);
        $remainingMinutes = $this->estimated_minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Get formatted missed date
     */
    public function getFormattedMissedDate(): string
    {
        return $this->missed_date->format('M j, Y');
    }

    /**
     * Get days since missed
     */
    public function getDaysSinceMissed(): int
    {
        return (int) $this->missed_date->diffInDays(Carbon::now());
    }

    /**
     * Get priority label
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            1 => 'Critical',
            2 => 'High',
            3 => 'Medium',
            4 => 'Low',
            5 => 'Later',
            default => 'Unknown',
        };
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            1 => 'bg-red-100 text-red-800',
            2 => 'bg-orange-100 text-orange-800',
            3 => 'bg-yellow-100 text-yellow-800',
            4 => 'bg-blue-100 text-blue-800',
            5 => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status color for UI display
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'reassigned' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if this catch-up is overdue (missed more than a week ago)
     */
    public function isOverdue(): bool
    {
        return $this->getDaysSinceMissed() > 7;
    }

    /**
     * Validate status value
     */
    public static function validateStatus(string $status): bool
    {
        return in_array($status, ['pending', 'reassigned', 'completed', 'cancelled']);
    }

    /**
     * Validate priority value
     */
    public static function validatePriority(int $priority): bool
    {
        return $priority >= 1 && $priority <= 5;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_session_id' => $this->original_session_id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'estimated_minutes' => $this->estimated_minutes,
            'formatted_duration' => $this->getFormattedDuration(),
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'priority_color' => $this->getPriorityColor(),
            'missed_date' => $this->missed_date->format('Y-m-d'),
            'formatted_missed_date' => $this->getFormattedMissedDate(),
            'days_since_missed' => $this->getDaysSinceMissed(),
            'reason' => $this->reason,
            'reassigned_to_session_id' => $this->reassigned_to_session_id,
            'status' => $this->status,
            'status_color' => $this->getStatusColor(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
