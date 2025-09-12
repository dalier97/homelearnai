<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $child_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property string $slot_type
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Child $child
 */
class ReviewSlot extends Model
{
    protected $fillable = [
        'child_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_type',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'slot_type' => 'micro',
        'is_active' => true,
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
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReviewSlot>
     */
    public static function forChild(int $childId): Collection
    {
        return self::where('child_id', $childId)
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReviewSlot>
     */
    public static function forChildAndDay(int $childId, int $dayOfWeek): Collection
    {
        return self::where('child_id', $childId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get today's review slots for a child
     */
    public static function getTodaySlots(int $childId): Collection
    {
        $todayDayOfWeek = Carbon::now()->dayOfWeekIso; // 1=Monday, 7=Sunday

        return self::forChildAndDay($childId, $todayDayOfWeek);
    }

    /**
     * Get active slots for current time
     */
    public static function getCurrentActiveSlots(int $childId): Collection
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso;

        return self::where('child_id', $childId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->get();
    }

    /**
     * Get upcoming slots for today
     */
    public static function getUpcomingTodaySlots(int $childId): Collection
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso;

        return self::where('child_id', $childId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '>', $currentTime)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    // Note: save() and delete() methods are now handled by Eloquent automatically

    // Relationship defined above using Eloquent belongsTo()

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): int
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time);
        $end = Carbon::createFromFormat('H:i:s', $this->end_time);

        return (int) $start->diffInMinutes($end);
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
     * Get time range formatted for display
     */
    public function getTimeRange(): string
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A');
        $end = Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:i A');

        return "{$start} - {$end}";
    }

    /**
     * Get day name
     */
    public function getDayName(): string
    {
        $days = ['', __('monday'), __('tuesday'), __('wednesday'), __('thursday'), __('friday'), __('saturday'), __('sunday')];

        return $days[$this->day_of_week] ?? __('unknown');
    }

    /**
     * Get slot type label
     */
    public function getSlotTypeLabel(): string
    {
        return match ($this->slot_type) {
            'micro' => __('micro_session'),
            'standard' => __('standard_session'),
            default => __('unknown'),
        };
    }

    /**
     * Get slot type color for UI
     */
    public function getSlotTypeColor(): string
    {
        return match ($this->slot_type) {
            'micro' => 'bg-green-100 text-green-800',
            'standard' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if slot is currently active
     */
    public function isCurrentlyActive(): bool
    {
        $now = Carbon::now();

        if ($now->dayOfWeekIso !== $this->day_of_week) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }

    /**
     * Check if slot is upcoming today
     */
    public function isUpcomingToday(): bool
    {
        $now = Carbon::now();

        if ($now->dayOfWeekIso !== $this->day_of_week) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        return $currentTime < $this->start_time;
    }

    /**
     * Get minutes until slot starts (if upcoming today)
     */
    public function getMinutesUntilStart(): ?int
    {
        if (! $this->isUpcomingToday()) {
            return null;
        }

        $now = Carbon::now();
        $slotStart = Carbon::createFromFormat('H:i:s', $this->start_time);
        $slotStart->setDate($now->year, $now->month, $now->day);

        return (int) $now->diffInMinutes($slotStart);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(): bool
    {
        $this->is_active = ! $this->is_active;

        return $this->save();
    }

    /**
     * Check if slot overlaps with another time range
     */
    public function overlapsWith(string $startTime, string $endTime): bool
    {
        return ! ($endTime <= $this->start_time || $startTime >= $this->end_time);
    }

    /**
     * Create default review slots for a child (called when child is created)
     */
    public static function createDefaultSlotsForChild(int $childId): bool
    {
        $slots = [];

        // Create morning micro-review slots (5min at 8:00 AM) for all 7 days
        foreach (range(1, 7) as $day) {
            $slots[] = [
                'child_id' => $childId,
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '08:05:00',
                'slot_type' => 'micro',
                'is_active' => true,
            ];

            // Create evening micro-review slots (5min at 7:30 PM)
            $slots[] = [
                'child_id' => $childId,
                'day_of_week' => $day,
                'start_time' => '19:30:00',
                'end_time' => '19:35:00',
                'slot_type' => 'micro',
                'is_active' => true,
            ];
        }

        return self::insert($slots);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'child_id' => $this->child_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->getDayName(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'time_range' => $this->getTimeRange(),
            'slot_type' => $this->slot_type,
            'slot_type_label' => $this->getSlotTypeLabel(),
            'slot_type_color' => $this->getSlotTypeColor(),
            'duration_minutes' => $this->getDurationMinutes(),
            'formatted_duration' => $this->getFormattedDuration(),
            'is_active' => $this->is_active,
            'is_currently_active' => $this->isCurrentlyActive(),
            'is_upcoming_today' => $this->isUpcomingToday(),
            'minutes_until_start' => $this->getMinutesUntilStart(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
