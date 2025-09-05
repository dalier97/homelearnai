<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Subject
{
    public ?int $id = null;

    public string $name;

    public string $color = '#3b82f6';

    public string $user_id;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

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
        $data = $supabase->from('subjects')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('subjects')
            ->eq($column, $value)
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forUser(string $userId, SupabaseClient $supabase): Collection
    {
        return self::where('user_id', $userId, $supabase);
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'name' => $this->name,
            'color' => $this->color,
            'user_id' => $this->user_id,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('subjects')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('subjects')->insert($data);
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

        return $supabase->from('subjects')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get all units for this subject
     */
    public function units(SupabaseClient $supabase): Collection
    {
        return $supabase->from('units')
            ->eq('subject_id', $this->id)
            ->orderBy('target_completion_date', 'asc')
            ->get()
            ->map(fn ($item) => new \App\Models\Unit($item));
    }

    /**
     * Get count of units in this subject
     */
    public function getUnitCount(SupabaseClient $supabase): int
    {
        $units = $supabase->from('units')
            ->eq('subject_id', $this->id)
            ->get();

        return count($units);
    }

    /**
     * Validate color is valid hex format
     */
    public static function validateColor(string $color): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    /**
     * Get predefined color options for subjects
     */
    public static function getColorOptions(): array
    {
        return [
            '#ef4444' => 'Red',
            '#f59e0b' => 'Orange',
            '#eab308' => 'Yellow',
            '#10b981' => 'Green',
            '#3b82f6' => 'Blue',
            '#6366f1' => 'Indigo',
            '#8b5cf6' => 'Purple',
            '#ec4899' => 'Pink',
            '#6b7280' => 'Gray',
            '#14b8a6' => 'Teal',
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
