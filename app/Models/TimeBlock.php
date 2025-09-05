<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimeBlock
{
    public ?int $id = null;

    public int $child_id;

    public int $day_of_week; // 1=Monday, 7=Sunday

    public string $start_time;

    public string $end_time;

    public string $label;

    public bool $is_imported = false;

    public string $commitment_type = 'preferred'; // fixed, preferred, flexible

    public ?string $source_uid = null;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

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

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('time_blocks')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('time_blocks')
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
        return $supabase->from('time_blocks')
            ->eq('child_id', $childId)
            ->eq('day_of_week', $dayOfWeek)
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
            'label' => $this->label,
            'is_imported' => $this->is_imported,
            'commitment_type' => $this->commitment_type,
            'source_uid' => $this->source_uid,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('time_blocks')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('time_blocks')->insert($data);
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

        return $supabase->from('time_blocks')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get the child this time block belongs to
     */
    public function child(SupabaseClient $supabase): ?Child
    {
        return Child::find($this->child_id, $supabase);
    }

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

        return $end->diffInMinutes($start);
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
