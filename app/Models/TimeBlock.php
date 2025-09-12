<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $child_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string $label
 * @property bool $is_imported
 * @property string $commitment_type
 * @property string|null $source_uid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Child $child
 */
class TimeBlock extends Model
{
    protected $fillable = [
        'child_id',
        'day_of_week',
        'start_time',
        'end_time',
        'label',
        'is_imported',
        'commitment_type',
        'source_uid',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_imported' => 'boolean',
    ];

    protected $attributes = [
        'is_imported' => false,
        'commitment_type' => 'preferred',
    ];

    // Day name mapping
    private static array $dayNames = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    /**
     * Eloquent relationships
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Scopes and query methods
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeBlock>
     */
    public static function forChild(int $childId): Collection
    {
        return self::where('child_id', $childId)
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeBlock>
     */
    public static function forChildAndDay(int $childId, int $dayOfWeek): Collection
    {
        return self::where('child_id', $childId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    // Note: save() and delete() methods are now handled by Eloquent automatically

    // Relationship defined above using Eloquent belongsTo()

    /**
     * Get day name
     */
    public function getDayName(): string
    {
        return self::$dayNames[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get short day name (3 letters)
     */
    public function getDayShort(): string
    {
        return substr($this->getDayName(), 0, 3);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time);
        $end = Carbon::createFromFormat('H:i:s', $this->end_time);

        return (int) $end->diffInMinutes($start);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        $minutes = $this->getDurationMinutes();

        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Get formatted time range
     */
    public function getTimeRange(): string
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A');
        $end = Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:i A');

        return "{$start} - {$end}";
    }

    /**
     * Check if time block overlaps with another
     */
    public function overlapsWith(TimeBlock $other): bool
    {
        if ($this->day_of_week !== $other->day_of_week) {
            return false;
        }

        $thisStart = Carbon::createFromFormat('H:i:s', $this->start_time);
        $thisEnd = Carbon::createFromFormat('H:i:s', $this->end_time);
        $otherStart = Carbon::createFromFormat('H:i:s', $other->start_time);
        $otherEnd = Carbon::createFromFormat('H:i:s', $other->end_time);

        return $thisStart->lt($otherEnd) && $thisEnd->gt($otherStart);
    }

    /**
     * Validate time format
     */
    public static function validateTime(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * Validate day of week
     */
    public static function validateDayOfWeek(int $dayOfWeek): bool
    {
        return $dayOfWeek >= 1 && $dayOfWeek <= 7;
    }

    /**
     * Get all day options for forms
     */
    public static function getDayOptions(): array
    {
        return self::$dayNames;
    }

    /**
     * Check if this is an imported/external event
     */
    public function isImported(): bool
    {
        return $this->is_imported;
    }

    /**
     * Check if this can be moved/rescheduled
     */
    public function canBeRescheduled(): bool
    {
        return $this->commitment_type !== 'fixed';
    }

    /**
     * Get commitment type display label
     */
    public function getCommitmentTypeLabel(): string
    {
        return match ($this->commitment_type) {
            'fixed' => 'Fixed',
            'preferred' => 'Preferred Time',
            'flexible' => 'Flexible',
            default => 'Unknown',
        };
    }

    /**
     * Get commitment type color for UI
     */
    public function getCommitmentTypeColor(): string
    {
        return match ($this->commitment_type) {
            'fixed' => 'bg-red-100 text-red-800 border-red-200',
            'preferred' => 'bg-blue-100 text-blue-800 border-blue-200',
            'flexible' => 'bg-green-100 text-green-800 border-green-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'child_id' => $this->child_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->getDayName(),
            'day_short' => $this->getDayShort(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'time_range' => $this->getTimeRange(),
            'duration_minutes' => $this->getDurationMinutes(),
            'formatted_duration' => $this->getFormattedDuration(),
            'label' => $this->label,
            'is_imported' => $this->is_imported,
            'commitment_type' => $this->commitment_type,
            'commitment_type_label' => $this->getCommitmentTypeLabel(),
            'commitment_type_color' => $this->getCommitmentTypeColor(),
            'source_uid' => $this->source_uid,
            'can_be_rescheduled' => $this->canBeRescheduled(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
