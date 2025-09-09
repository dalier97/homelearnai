<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReviewSlot
{
    public ?int $id = null;

    public int $child_id;

    public int $day_of_week; // 1-7 for Monday-Sunday

    public string $start_time; // HH:mm:ss format

    public string $end_time; // HH:mm:ss format

    public string $slot_type = 'micro'; // micro, standard

    public bool $is_active = true;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } elseif ($key === 'is_active') {
                    $this->$key = $value === 'true' || $value === '1' || $value === true || $value === 1;
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(string $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('review_slots')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('review_slots')
            ->eq($column, $value)
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forChild(int $childId, SupabaseClient $supabase): Collection
    {
        return self::where('child_id', $childId, $supabase);
    }

    public static function forChildAndDay(int $childId, int $dayOfWeek, SupabaseClient $supabase): Collection
    {
        return $supabase->from('review_slots')
            ->eq('child_id', $childId)
            ->eq('day_of_week', $dayOfWeek)
            ->eq('is_active', true)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    /**
     * Get today's review slots for a child
     */
    public static function getTodaySlots(int $childId, SupabaseClient $supabase): Collection
    {
        $todayDayOfWeek = Carbon::now()->dayOfWeekIso; // 1=Monday, 7=Sunday

        return self::forChildAndDay($childId, $todayDayOfWeek, $supabase);
    }

    /**
     * Get active slots for current time
     */
    public static function getCurrentActiveSlots(int $childId, SupabaseClient $supabase): Collection
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso;

        return $supabase->from('review_slots')
            ->eq('child_id', $childId)
            ->eq('day_of_week', $dayOfWeek)
            ->eq('is_active', true)
            ->lte('start_time', $currentTime)
            ->gte('end_time', $currentTime)
            ->get()
            ->map(fn ($item) => new self($item));
    }

    /**
     * Get upcoming slots for today
     */
    public static function getUpcomingTodaySlots(int $childId, SupabaseClient $supabase): Collection
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso;

        return $supabase->from('review_slots')
            ->eq('child_id', $childId)
            ->eq('day_of_week', $dayOfWeek)
            ->eq('is_active', true)
            ->gt('start_time', $currentTime)
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'child_id' => $this->child_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'slot_type' => $this->slot_type,
            'is_active' => $this->is_active,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('review_slots')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('review_slots')->insert($data);
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

        return $supabase->from('review_slots')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get related models
     */
    public function child(SupabaseClient $supabase): ?Child
    {
        return Child::find((int) $this->child_id);
    }

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
    public function toggleActive(SupabaseClient $supabase): bool
    {
        $this->is_active = ! $this->is_active;

        return $this->save($supabase);
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
    public static function createDefaultSlotsForChild(int $childId, SupabaseClient $supabase): bool
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

        $result = $supabase->from('review_slots')->insert($slots);

        return ! empty($result);
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
