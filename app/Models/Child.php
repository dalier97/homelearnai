<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Child
{
    public ?int $id = null;

    public string $name;

    public int $age;

    public string $user_id;

    public int $independence_level = 1; // 1-4 levels

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
        $data = $supabase->from('children')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        try {
            return $supabase->from('children')
                ->eq($column, $value)
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn ($item) => new self($item));
        } catch (\Exception $e) {
            // Log the error for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->error('Child query failed', [
                    'column' => $column,
                    'value' => $value,
                    'error' => $e->getMessage(),
                ]);
            }

            return collect([]);
        }
    }

    public static function forUser(string $userId, SupabaseClient $supabase): Collection
    {
        if (empty($userId)) {
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->warning('Child::forUser called with empty user_id');
            }

            return collect([]);
        }

        // Log the query attempt
        if (function_exists('app') && app() && app()->has('log')) {
            app('log')->debug('Child::forUser query attempt', [
                'user_id' => $userId,
                'user_id_length' => strlen($userId),
            ]);
        }

        $result = self::where('user_id', $userId, $supabase);

        // Log the result
        if (function_exists('app') && app() && app()->has('log')) {
            app('log')->debug('Child::forUser query result', [
                'user_id' => $userId,
                'children_count' => $result->count(),
                'children_ids' => $result->pluck('id')->toArray(),
            ]);
        }

        return $result;
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'name' => $this->name,
            'age' => $this->age,
            'user_id' => $this->user_id,
            'independence_level' => $this->independence_level,
        ];

        try {
            if ($this->id) {
                // Update existing
                $result = $supabase->from('children')
                    ->eq('id', $this->id)
                    ->update($data);
            } else {
                // Create new
                $result = $supabase->from('children')->insert($data);
                if ($result && isset($result[0]['id'])) {
                    $this->id = $result[0]['id'];
                    $this->created_at = Carbon::now();

                    // Create default time blocks for a typical school week
                    $this->createDefaultTimeBlocks($supabase);
                }
            }

            return ! empty($result);
        } catch (\Exception $e) {
            // Log the error for debugging
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->error('Child save failed', [
                    'data' => $data,
                    'user_id' => $this->user_id,
                    'error' => $e->getMessage(),
                    'existing_id' => $this->id,
                ]);
            }

            return false;
        }
    }

    public function delete(SupabaseClient $supabase): bool
    {
        if (! $this->id) {
            return false;
        }

        return $supabase->from('children')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get time blocks for this child
     */
    public function timeBlocks(SupabaseClient $supabase): Collection
    {
        return $supabase->from('time_blocks')
            ->eq('child_id', $this->id)
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($item) => new \App\Models\TimeBlock($item));
    }

    /**
     * Get age group for appropriate content selection
     */
    public function getAgeGroup(): string
    {
        if ($this->age <= 5) {
            return 'preschool';
        } elseif ($this->age <= 8) {
            return 'elementary_early';
        } elseif ($this->age <= 11) {
            return 'elementary_late';
        } elseif ($this->age <= 14) {
            return 'middle_school';
        } else {
            return 'high_school';
        }
    }

    /**
     * Create default time blocks for a new child
     */
    private function createDefaultTimeBlocks(SupabaseClient $supabase): void
    {
        // Create age-appropriate default time blocks
        $defaultBlocks = [];

        // Monday to Friday morning blocks (9:00 AM - 11:00 AM)
        for ($day = 1; $day <= 5; $day++) {
            $defaultBlocks[] = [
                'child_id' => $this->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'label' => 'Morning Learning',
            ];
        }

        // Monday to Friday afternoon blocks (1:00 PM - 3:00 PM)
        for ($day = 1; $day <= 5; $day++) {
            $defaultBlocks[] = [
                'child_id' => $this->id,
                'day_of_week' => $day,
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'label' => 'Afternoon Learning',
            ];
        }

        try {
            $supabase->from('time_blocks')->insert($defaultBlocks);
        } catch (\Exception $e) {
            // Log error but don't fail child creation
            if (function_exists('app') && app() && app()->has('log')) {
                app('log')->warning('Failed to create default time blocks for child', [
                    'child_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get independence level label
     */
    public function getIndependenceLevelLabel(): string
    {
        return match ($this->independence_level) {
            1 => 'Guided (View Only)',
            2 => 'Basic (Reorder Tasks)',
            3 => 'Intermediate (Move Within Week)',
            4 => 'Advanced (Plan Proposals)',
            default => 'Guided'
        };
    }

    /**
     * Check if child can reorder today's tasks
     */
    public function canReorderTasks(): bool
    {
        return $this->independence_level >= 2;
    }

    /**
     * Check if child can move sessions within week
     */
    public function canMoveSessionsInWeek(): bool
    {
        return $this->independence_level >= 3;
    }

    /**
     * Check if child can propose weekly plans
     */
    public function canProposeWeeklyPlans(): bool
    {
        return $this->independence_level >= 4;
    }

    /**
     * Check if child has view-only access
     */
    public function isViewOnlyMode(): bool
    {
        return $this->independence_level === 1;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'user_id' => $this->user_id,
            'independence_level' => $this->independence_level,
            'independence_level_label' => $this->getIndependenceLevelLabel(),
            'can_reorder_tasks' => $this->canReorderTasks(),
            'can_move_sessions_in_week' => $this->canMoveSessionsInWeek(),
            'can_propose_weekly_plans' => $this->canProposeWeeklyPlans(),
            'is_view_only_mode' => $this->isViewOnlyMode(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'age_group' => $this->getAgeGroup(),
        ];
    }
}
