<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Topic
{
    public ?int $id = null;

    public int $unit_id;

    public string $title;

    public int $estimated_minutes = 30;

    public array $prerequisites = [];

    public bool $required = true;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } elseif ($key === 'prerequisites' && is_string($value)) {
                    // Handle PostgreSQL array format from Supabase
                    $this->$key = $this->parsePostgreSQLArray($value);
                } elseif ($key === 'prerequisites' && is_array($value)) {
                    $this->$key = $value;
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('topics')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('topics')
            ->eq($column, $value)
            ->orderBy('required', 'desc')
            ->orderBy('title', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forUnit(int $unitId, SupabaseClient $supabase): Collection
    {
        return self::where('unit_id', $unitId, $supabase);
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'unit_id' => $this->unit_id,
            'title' => $this->title,
            'estimated_minutes' => $this->estimated_minutes,
            'prerequisites' => $this->prerequisites,
            'required' => $this->required,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('topics')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('topics')->insert($data);
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

        return $supabase->from('topics')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get the unit this topic belongs to
     */
    public function unit(SupabaseClient $supabase): ?Unit
    {
        return Unit::find($this->unit_id, $supabase);
    }

    /**
     * Get the subject this topic belongs to (through unit)
     */
    public function subject(SupabaseClient $supabase): ?Subject
    {
        $unit = $this->unit($supabase);

        return $unit ? $unit->subject($supabase) : null;
    }

    /**
     * Get prerequisite topics
     */
    public function getPrerequisiteTopics(SupabaseClient $supabase): Collection
    {
        if (empty($this->prerequisites)) {
            return collect([]);
        }

        $topics = collect([]);
        foreach ($this->prerequisites as $topicId) {
            $topic = self::find($topicId, $supabase);
            if ($topic) {
                $topics->push($topic);
            }
        }

        return $topics;
    }

    /**
     * Check if all prerequisites are met
     * Note: This is a simplified check - in a real app you'd track completion status
     */
    public function hasPrerequisitesMet(SupabaseClient $supabase): bool
    {
        // For now, return true - in a real app you'd check completion status
        return true;
    }

    /**
     * Get estimated duration in human readable format
     */
    public function getEstimatedDuration(): string
    {
        if ($this->estimated_minutes < 60) {
            return "{$this->estimated_minutes} min";
        }

        $hours = floor($this->estimated_minutes / 60);
        $minutes = $this->estimated_minutes % 60;

        if ($minutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$minutes}m";
    }

    /**
     * Parse PostgreSQL array format like "{1,2,3}" to PHP array
     */
    private function parsePostgreSQLArray(string $value): array
    {
        if ($value === '{}' || empty($value)) {
            return [];
        }

        $value = trim($value, '{}');
        if (empty($value)) {
            return [];
        }

        return array_map('intval', explode(',', $value));
    }

    /**
     * Validate estimated_minutes is within reasonable bounds
     */
    public static function validateEstimatedMinutes(int $minutes): bool
    {
        return $minutes > 0 && $minutes <= 480; // 8 hours max
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit_id' => $this->unit_id,
            'title' => $this->title,
            'estimated_minutes' => $this->estimated_minutes,
            'estimated_duration' => $this->getEstimatedDuration(),
            'prerequisites' => $this->prerequisites,
            'required' => $this->required,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'has_prerequisites_met' => true, // Simplified for now
        ];
    }
}
