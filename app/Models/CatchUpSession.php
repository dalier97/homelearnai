<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $original_session_id
 * @property int $child_id
 * @property int $topic_id
 * @property int $estimated_minutes
 * @property int $priority
 * @property \Carbon\Carbon $missed_date
 * @property string|null $reason
 * @property int|null $reassigned_to_session_id
 * @property string $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Session $originalSession
 * @property-read \App\Models\Session|null $reassignedToSession
 * @property-read \App\Models\Child $child
 * @property-read \App\Models\Topic $topic
 */
class CatchUpSession extends Model
{
    protected $fillable = [
        'original_session_id',
        'child_id',
        'topic_id',
        'estimated_minutes',
        'priority',
        'missed_date',
        'reason',
        'reassigned_to_session_id',
        'status',
    ];

    protected $casts = [
        'estimated_minutes' => 'integer',
        'priority' => 'integer',
        'missed_date' => 'date',
    ];

    protected $attributes = [
        'priority' => 1,
        'status' => 'pending',
    ];

    /**
     * Eloquent relationships
     */
    public function originalSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'original_session_id');
    }

    public function reassignedToSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'reassigned_to_session_id');
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
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatchUpSession>
     */
    public static function forChild(int $childId): Collection
    {
        return self::where('child_id', $childId)
            ->orderBy('priority', 'asc')
            ->orderBy('missed_date', 'desc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatchUpSession>
     */
    public static function forChildAndStatus(int $childId, string $status): Collection
    {
        return self::where('child_id', $childId)
            ->where('status', $status)
            ->orderBy('priority', 'asc')
            ->orderBy('missed_date', 'desc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatchUpSession>
     */
    public static function pending(int $childId): Collection
    {
        return self::forChildAndStatus($childId, 'pending');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatchUpSession>
     */
    public static function byPriority(int $childId, int $priority): Collection
    {
        return self::where('child_id', $childId)
            ->where('priority', $priority)
            ->orderBy('missed_date', 'desc')
            ->get();
    }

    // Note: save() and delete() methods are now handled by Eloquent automatically

    // Relationships defined above using Eloquent methods

    /**
     * Get the unit this catch-up belongs to (through topic)
     */
    public function unit(): ?Unit
    {
        return $this->topic->unit;
    }

    /**
     * Get the subject this catch-up belongs to (through topic -> unit)
     */
    public function subject(): ?Subject
    {
        return $this->topic->subject;
    }

    /**
     * Mark as reassigned to a specific session
     */
    public function reassignToSession(int $sessionId): bool
    {
        $this->reassigned_to_session_id = $sessionId;
        $this->status = 'reassigned';

        return $this->save();
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): bool
    {
        $this->status = 'completed';

        return $this->save();
    }

    /**
     * Mark as cancelled
     */
    public function markCancelled(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->reason = $reason;

        return $this->save();
    }

    /**
     * Update priority (1=highest, 5=lowest)
     */
    public function updatePriority(int $priority): bool
    {
        if ($priority < 1 || $priority > 5) {
            throw new \InvalidArgumentException('Priority must be between 1 and 5');
        }

        $this->priority = $priority;

        return $this->save();
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
