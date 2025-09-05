<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Unit
{
    public ?int $id = null;

    public int $subject_id;

    public string $name;

    public ?string $description = null;

    public ?Carbon $target_completion_date = null;

    public int $completed_topics_count = 0;

    public int $total_topics_count = 0;

    public float $completion_percentage = 0.00;

    public bool $can_complete = true;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['target_completion_date', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('units')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('units')
            ->eq($column, $value)
            ->orderBy('target_completion_date', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forSubject(int $subjectId, SupabaseClient $supabase): Collection
    {
        return self::where('subject_id', $subjectId, $supabase);
    }

    public function save(SupabaseClient $supabase): bool
    {
        // Only include actual database columns, not computed fields
        $data = [
            'subject_id' => $this->subject_id,
            'name' => $this->name,
            'description' => $this->description,
            'target_completion_date' => $this->target_completion_date?->format('Y-m-d'),
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('units')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('units')->insert($data);
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

        return $supabase->from('units')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get the subject this unit belongs to
     */
    public function subject(SupabaseClient $supabase): ?Subject
    {
        return Subject::find($this->subject_id, $supabase);
    }

    /**
     * Get all topics for this unit
     */
    public function topics(SupabaseClient $supabase): Collection
    {
        return $supabase->from('topics')
            ->eq('unit_id', $this->id)
            ->orderBy('required', 'desc')
            ->orderBy('title', 'asc')
            ->get()
            ->map(fn ($item) => new \App\Models\Topic($item));
    }

    /**
     * Get count of topics in this unit
     */
    public function getTopicCount(SupabaseClient $supabase): int
    {
        $topics = $supabase->from('topics')
            ->eq('unit_id', $this->id)
            ->get();

        return count($topics);
    }

    /**
     * Get progress statistics for this unit
     */
    public function getProgressStats(SupabaseClient $supabase): array
    {
        $topics = $this->topics($supabase);
        $totalTopics = $topics->count();
        $requiredTopics = $topics->where('required', true)->count();

        return [
            'total_topics' => $totalTopics,
            'required_topics' => $requiredTopics,
            'optional_topics' => $totalTopics - $requiredTopics,
            'estimated_hours' => round($topics->sum('estimated_minutes') / 60, 1),
            'completed_topics_count' => $this->completed_topics_count,
            'completion_percentage' => $this->completion_percentage,
            'can_complete' => $this->can_complete,
        ];
    }

    /**
     * Get detailed progress for a specific child
     */
    public function getProgressForChild(int $childId, SupabaseClient $supabase): array
    {
        $topics = $this->topics($supabase);
        $requiredTopics = $topics->where('required', true);

        // Get sessions for this unit's topics and child
        $completedSessions = collect([]);
        foreach ($topics as $topic) {
            $sessions = Session::where('topic_id', $topic->id, $supabase)
                ->where('child_id', $childId)
                ->where('status', 'done');
            $completedSessions = $completedSessions->merge($sessions);
        }

        $completedTopicIds = $completedSessions->pluck('topic_id')->unique();
        $completedRequiredTopics = $requiredTopics->whereIn('id', $completedTopicIds);

        $completionPercentage = $requiredTopics->count() > 0
            ? ($completedRequiredTopics->count() / $requiredTopics->count()) * 100
            : 0;

        return [
            'total_topics' => $topics->count(),
            'required_topics' => $requiredTopics->count(),
            'completed_topics' => $completedTopicIds->count(),
            'completed_required_topics' => $completedRequiredTopics->count(),
            'completion_percentage' => round($completionPercentage, 2),
            'can_complete' => $completedRequiredTopics->count() >= $requiredTopics->count(),
            'remaining_required' => $requiredTopics->count() - $completedRequiredTopics->count(),
            'topics_breakdown' => [
                'completed' => $completedTopicIds->toArray(),
                'required_remaining' => $requiredTopics->whereNotIn('id', $completedTopicIds)->pluck('id')->toArray(),
                'optional_remaining' => $topics->where('required', false)->whereNotIn('id', $completedTopicIds)->pluck('id')->toArray(),
            ],
        ];
    }

    /**
     * Get progress bar data for UI display
     */
    public function getProgressBarData(int $childId, SupabaseClient $supabase): array
    {
        $progress = $this->getProgressForChild($childId, $supabase);

        $color = 'bg-gray-200';
        if ($progress['completion_percentage'] >= 100) {
            $color = 'bg-green-500';
        } elseif ($progress['completion_percentage'] >= 75) {
            $color = 'bg-blue-500';
        } elseif ($progress['completion_percentage'] >= 50) {
            $color = 'bg-yellow-500';
        } elseif ($progress['completion_percentage'] >= 25) {
            $color = 'bg-orange-500';
        } else {
            $color = 'bg-red-500';
        }

        return [
            'percentage' => $progress['completion_percentage'],
            'color' => $color,
            'text' => $progress['completed_required_topics'].'/'.$progress['required_topics'].' required topics',
            'status' => $progress['can_complete'] ? 'complete' : 'in_progress',
            'total_sessions' => $progress['completed_topics'],
        ];
    }

    /**
     * Check if unit meets completion gate requirements
     */
    public function meetsCompletionGate(int $childId, SupabaseClient $supabase): bool
    {
        $progress = $this->getProgressForChild($childId, $supabase);

        return $progress['can_complete'];
    }

    /**
     * Get completion status label
     */
    public function getCompletionStatus(int $childId, SupabaseClient $supabase): string
    {
        $progress = $this->getProgressForChild($childId, $supabase);

        if ($progress['can_complete']) {
            return 'Complete';
        } elseif ($progress['completion_percentage'] >= 75) {
            return 'Nearly Complete';
        } elseif ($progress['completion_percentage'] >= 50) {
            return 'In Progress';
        } elseif ($progress['completion_percentage'] >= 25) {
            return 'Started';
        } else {
            return 'Not Started';
        }
    }

    /**
     * Get next topics that should be worked on
     */
    public function getNextTopics(int $childId, SupabaseClient $supabase, int $limit = 3): Collection
    {
        $progress = $this->getProgressForChild($childId, $supabase);
        $topics = $this->topics($supabase);

        // Get topics that are not yet completed
        $remainingTopics = $topics->whereNotIn('id', $progress['topics_breakdown']['completed']);

        // Prioritize required topics first
        $requiredRemaining = $remainingTopics->where('required', true);
        $optionalRemaining = $remainingTopics->where('required', false);

        // Combine with required first
        $nextTopics = $requiredRemaining->merge($optionalRemaining)->take($limit);

        return $nextTopics;
    }

    /**
     * Check if this unit is overdue
     */
    public function isOverdue(): bool
    {
        return $this->target_completion_date
            && $this->target_completion_date->isPast();
    }

    /**
     * Get days until target completion
     */
    public function getDaysUntilTarget(): ?int
    {
        if (! $this->target_completion_date) {
            return null;
        }

        return now()->diffInDays($this->target_completion_date, false);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'name' => $this->name,
            'description' => $this->description,
            'target_completion_date' => $this->target_completion_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'days_until_target' => $this->getDaysUntilTarget(),
            'completed_topics_count' => $this->completed_topics_count,
            'total_topics_count' => $this->total_topics_count,
            'completion_percentage' => $this->completion_percentage,
            'can_complete' => $this->can_complete,
        ];
    }
}
