<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Task
{
    public ?int $id = null;

    public string $title;

    public ?string $description;

    public string $priority = 'medium';

    public string $status = 'pending';

    public string $user_id;

    public ?Carbon $due_date = null;

    public ?Carbon $completed_at = null;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['due_date', 'completed_at', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('tasks')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('tasks')
            ->eq($column, $value)
            ->orderBy('created_at', 'desc')
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
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'due_date' => $this->due_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('tasks')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('tasks')->insert($data);
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

        return $supabase->from('tasks')
            ->eq('id', $this->id)
            ->delete();
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
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'due_date' => $this->due_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
        ];
    }
}
