<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'user_id',
        'due_date',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'priority' => 'medium',
        'status' => 'pending',
    ];

    /**
     * Get the user that owns the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get tasks for a specific user
     */
    public function scopeForUser($query, int|string $userId)
    {
        return $query->where('user_id', $userId)->orderBy('created_at', 'desc');
    }

    public function toggleComplete(): void
    {
        if ($this->status === 'completed') {
            $this->status = 'pending';
            $this->completed_at = null;
        } else {
            $this->status = 'completed';
            $this->completed_at = Carbon::now();
        }
        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        // Add computed attributes
        $array['is_overdue'] = $this->isOverdue();

        // Format timestamps
        if ($this->due_date) {
            $array['due_date'] = $this->due_date->toIso8601String();
        }
        if ($this->completed_at) {
            $array['completed_at'] = $this->completed_at->toIso8601String();
        }
        if ($this->created_at) {
            $array['created_at'] = $this->created_at->toIso8601String();
        }
        if ($this->updated_at) {
            $array['updated_at'] = $this->updated_at->toIso8601String();
        }

        return $array;
    }
}
