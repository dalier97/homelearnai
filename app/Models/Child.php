<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property int $age
 * @property int $user_id
 * @property int $independence_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Support\Collection $timeBlocks
 */
class Child extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'age',
        'user_id',
        'independence_level',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'age' => 'integer',
        'independence_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'independence_level' => 1,
    ];

    /**
     * Get the user that owns the child.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subjects for this child.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get time blocks for this child.
     * Note: TimeBlock is still using Supabase pattern, so this provides compatibility
     */
    public function timeBlocks(?SupabaseClient $supabase = null): Collection
    {
        if (! $supabase) {
            // Return empty collection if no SupabaseClient provided
            return collect();
        }

        return TimeBlock::where('child_id', $this->id, $supabase);
    }

    /**
     * Accessor for timeBlocks attribute (backward compatibility)
     */
    public function getTimeBlocksAttribute(): Collection
    {
        // Return empty collection until TimeBlock is converted to Eloquent
        return collect();
    }

    /**
     * Scope to get children for a specific user
     */
    public function scopeForUser($query, int|string $userId)
    {
        return $query->where('user_id', $userId)->orderBy('name');
    }

    /**
     * Static method for backward compatibility with controllers that pass SupabaseClient
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Child>
     */
    public static function forUser(int|string $userId, ?SupabaseClient $supabase = null): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)->orderBy('name')->get();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Child $child) {
            // TODO: Create default time blocks when TimeBlock is converted to Eloquent
            // $child->createDefaultTimeBlocks();
        });
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
        $array = parent::toArray();

        // Add computed attributes
        $array['independence_level_label'] = $this->getIndependenceLevelLabel();
        $array['can_reorder_tasks'] = $this->canReorderTasks();
        $array['can_move_sessions_in_week'] = $this->canMoveSessionsInWeek();
        $array['can_propose_weekly_plans'] = $this->canProposeWeeklyPlans();
        $array['is_view_only_mode'] = $this->isViewOnlyMode();
        $array['age_group'] = $this->getAgeGroup();

        // Format timestamps
        if ($this->created_at) {
            $array['created_at'] = $this->created_at->toIso8601String();
        }
        if ($this->updated_at) {
            $array['updated_at'] = $this->updated_at->toIso8601String();
        }

        return $array;
    }
}
