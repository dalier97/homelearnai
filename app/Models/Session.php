<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Session
{
    public ?int $id = null;

    public int $topic_id;

    public int $child_id;

    public int $estimated_minutes;

    public string $status = 'backlog'; // backlog, planned, scheduled, done

    public string $commitment_type = 'preferred'; // fixed, preferred, flexible

    public ?int $scheduled_day_of_week = null; // 1-7 for Monday-Sunday

    public ?string $scheduled_start_time = null; // HH:mm:ss format

    public ?string $scheduled_end_time = null; // HH:mm:ss format

    public ?Carbon $scheduled_date = null; // Specific date when scheduled

    public ?Carbon $skipped_from_date = null; // Date when this session was skipped

    public ?string $notes = null;

    public ?Carbon $completed_at = null;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    // Evidence capture fields
    public ?string $evidence_notes = null;

    public ?array $evidence_photos = null; // Array of file paths/URLs

    public ?string $evidence_voice_memo = null; // File path/URL for voice recording

    public ?array $evidence_attachments = null; // Array of additional file paths

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['completed_at', 'scheduled_date', 'skipped_from_date', 'created_at', 'updated_at']) && $value) {
                    $this->$key = Carbon::parse($value);
                } elseif (in_array($key, ['evidence_photos', 'evidence_attachments']) && is_string($value)) {
                    // Handle PostgreSQL array format from database
                    $this->$key = $value ? json_decode($value, true) : null;
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function find(int $id, SupabaseClient $supabase): ?self
    {
        $data = $supabase->from('sessions')
            ->eq('id', $id)
            ->single();

        return $data ? new self($data) : null;
    }

    public static function where(string $column, mixed $value, SupabaseClient $supabase): Collection
    {
        return $supabase->from('sessions')
            ->eq($column, $value)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forChild(int $childId, SupabaseClient $supabase): Collection
    {
        return self::where('child_id', $childId, $supabase);
    }

    public static function forTopic(int $topicId, SupabaseClient $supabase): Collection
    {
        return self::where('topic_id', $topicId, $supabase);
    }

    public static function forChildAndStatus(int $childId, string $status, SupabaseClient $supabase): Collection
    {
        return $supabase->from('sessions')
            ->eq('child_id', $childId)
            ->eq('status', $status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public static function forChildAndDay(int $childId, int $dayOfWeek, SupabaseClient $supabase): Collection
    {
        return $supabase->from('sessions')
            ->eq('child_id', $childId)
            ->eq('scheduled_day_of_week', $dayOfWeek)
            ->eq('status', 'scheduled')
            ->orderBy('scheduled_start_time', 'asc')
            ->get()
            ->map(fn ($item) => new self($item));
    }

    public function save(SupabaseClient $supabase): bool
    {
        $data = [
            'topic_id' => $this->topic_id,
            'child_id' => $this->child_id,
            'estimated_minutes' => $this->estimated_minutes,
            'status' => $this->status,
            'commitment_type' => $this->commitment_type,
            'scheduled_day_of_week' => $this->scheduled_day_of_week,
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'skipped_from_date' => $this->skipped_from_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->toIso8601String(),
            // Evidence fields
            'evidence_notes' => $this->evidence_notes,
            'evidence_photos' => $this->evidence_photos ? json_encode($this->evidence_photos) : null,
            'evidence_voice_memo' => $this->evidence_voice_memo,
            'evidence_attachments' => $this->evidence_attachments ? json_encode($this->evidence_attachments) : null,
        ];

        if ($this->id) {
            // Update existing
            $result = $supabase->from('sessions')
                ->eq('id', $this->id)
                ->update($data);
        } else {
            // Create new
            $result = $supabase->from('sessions')->insert($data);
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

        return $supabase->from('sessions')
            ->eq('id', $this->id)
            ->delete();
    }

    /**
     * Get the topic this session belongs to
     */
    public function topic(SupabaseClient $supabase): ?Topic
    {
        return Topic::find($this->topic_id, $supabase);
    }

    /**
     * Get the child this session is for
     */
    public function child(SupabaseClient $supabase): ?Child
    {
        return Child::find($this->child_id, $supabase);
    }

    /**
     * Get the unit this session belongs to (through topic)
     */
    public function unit(SupabaseClient $supabase): ?Unit
    {
        $topic = $this->topic($supabase);

        return $topic ? $topic->unit($supabase) : null;
    }

    /**
     * Get the subject this session belongs to (through topic -> unit)
     */
    public function subject(SupabaseClient $supabase): ?Subject
    {
        $topic = $this->topic($supabase);

        return $topic ? $topic->subject($supabase) : null;
    }

    /**
     * Update session status
     */
    public function updateStatus(string $status, SupabaseClient $supabase): bool
    {
        $this->status = $status;

        if ($status === 'done' && ! $this->completed_at) {
            $this->completed_at = Carbon::now();
        } elseif ($status !== 'done') {
            $this->completed_at = null;
        }

        $success = $this->save($supabase);

        // Automatically create review when session is completed
        if ($success && $status === 'done' && $this->completed_at) {
            $existingReview = Review::forSession($this->id, $supabase);
            if (! $existingReview) {
                Review::createFromSession($this, $supabase);
            }
        }

        return $success;
    }

    /**
     * Schedule this session to a specific time slot
     */
    public function scheduleToTimeSlot(int $dayOfWeek, string $startTime, string $endTime, SupabaseClient $supabase, ?Carbon $date = null): bool
    {
        $this->status = 'scheduled';
        $this->scheduled_day_of_week = $dayOfWeek;
        $this->scheduled_start_time = $startTime;
        $this->scheduled_end_time = $endTime;
        $this->scheduled_date = $date;

        return $this->save($supabase);
    }

    /**
     * Move session back to planning status
     */
    public function unschedule(SupabaseClient $supabase): bool
    {
        $this->status = 'planned';
        $this->scheduled_day_of_week = null;
        $this->scheduled_start_time = null;
        $this->scheduled_end_time = null;
        $this->scheduled_date = null;

        return $this->save($supabase);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        if ($this->estimated_minutes < 60) {
            return "{$this->estimated_minutes}m";
        }

        $hours = floor($this->estimated_minutes / 60);
        $remainingMinutes = $this->estimated_minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Get scheduled time range
     */
    public function getScheduledTimeRange(): ?string
    {
        if (! $this->scheduled_start_time || ! $this->scheduled_end_time) {
            return null;
        }

        $start = Carbon::createFromFormat('H:i:s', $this->scheduled_start_time)->format('g:i A');
        $end = Carbon::createFromFormat('H:i:s', $this->scheduled_end_time)->format('g:i A');

        return "{$start} - {$end}";
    }

    /**
     * Get day name for scheduled day
     */
    public function getScheduledDayName(): ?string
    {
        if (! $this->scheduled_day_of_week) {
            return null;
        }

        $days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return $days[$this->scheduled_day_of_week] ?? null;
    }

    /**
     * Check if session fits within a time block
     */
    public function fitsInTimeBlock(TimeBlock $timeBlock): bool
    {
        if ($timeBlock->getDurationMinutes() < $this->estimated_minutes) {
            return false;
        }

        return true;
    }

    /**
     * Get status color for UI display
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'backlog' => 'bg-gray-100 text-gray-800',
            'planned' => 'bg-blue-100 text-blue-800',
            'scheduled' => 'bg-green-100 text-green-800',
            'done' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Skip this session (move to catch-up and create replacement suggestions)
     */
    public function skipDay(Carbon $originalDate, ?string $reason, SupabaseClient $supabase): CatchUpSession
    {
        // Create catch-up session
        $catchUpSession = new CatchUpSession([
            'original_session_id' => $this->id,
            'child_id' => $this->child_id,
            'topic_id' => $this->topic_id,
            'estimated_minutes' => $this->estimated_minutes,
            'priority' => $this->getPriorityFromCommitmentType(),
            'missed_date' => $originalDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $catchUpSession->save($supabase);

        // Update this session with skipped date
        $this->skipped_from_date = $originalDate;
        $this->save($supabase);

        return $catchUpSession;
    }

    /**
     * Get priority level based on commitment type for catch-up
     */
    private function getPriorityFromCommitmentType(): int
    {
        return match ($this->commitment_type) {
            'fixed' => 1, // Critical - was supposed to be fixed
            'preferred' => 2, // High priority
            'flexible' => 3, // Medium priority
            default => 3,
        };
    }

    /**
     * Check if this session can be moved/rescheduled
     */
    public function canBeRescheduled(): bool
    {
        return $this->commitment_type !== 'fixed';
    }

    /**
     * Get commitment type label for UI
     */
    public function getCommitmentTypeLabel(): string
    {
        return match ($this->commitment_type) {
            'fixed' => 'Fixed',
            'preferred' => 'Preferred',
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
            'fixed' => 'bg-red-100 text-red-800',
            'preferred' => 'bg-yellow-100 text-yellow-800',
            'flexible' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get catch-up sessions for this original session
     */
    public function catchUpSessions(SupabaseClient $supabase): Collection
    {
        return CatchUpSession::where('original_session_id', $this->id, $supabase);
    }

    /**
     * Check if session was skipped
     */
    public function wasSkipped(): bool
    {
        return $this->skipped_from_date !== null;
    }

    /**
     * Get formatted skipped date
     */
    public function getFormattedSkippedDate(): ?string
    {
        return $this->skipped_from_date?->format('M j, Y');
    }

    /**
     * Update commitment type
     */
    public function updateCommitmentType(string $commitmentType, SupabaseClient $supabase): bool
    {
        if (! self::validateCommitmentType($commitmentType)) {
            throw new \InvalidArgumentException('Invalid commitment type');
        }

        $this->commitment_type = $commitmentType;

        return $this->save($supabase);
    }

    /**
     * Validate status value
     */
    public static function validateStatus(string $status): bool
    {
        return in_array($status, ['backlog', 'planned', 'scheduled', 'done']);
    }

    /**
     * Validate commitment type value
     */
    public static function validateCommitmentType(string $commitmentType): bool
    {
        return in_array($commitmentType, ['fixed', 'preferred', 'flexible']);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->topic_id,
            'child_id' => $this->child_id,
            'estimated_minutes' => $this->estimated_minutes,
            'formatted_duration' => $this->getFormattedDuration(),
            'status' => $this->status,
            'status_color' => $this->getStatusColor(),
            'commitment_type' => $this->commitment_type,
            'commitment_type_label' => $this->getCommitmentTypeLabel(),
            'commitment_type_color' => $this->getCommitmentTypeColor(),
            'can_be_rescheduled' => $this->canBeRescheduled(),
            'was_skipped' => $this->wasSkipped(),
            'skipped_from_date' => $this->skipped_from_date?->format('Y-m-d'),
            'formatted_skipped_date' => $this->getFormattedSkippedDate(),
            'scheduled_day_of_week' => $this->scheduled_day_of_week,
            'scheduled_day_name' => $this->getScheduledDayName(),
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'scheduled_time_range' => $this->getScheduledTimeRange(),
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // Evidence fields
            'evidence_notes' => $this->evidence_notes,
            'evidence_photos' => $this->evidence_photos ?? [],
            'evidence_voice_memo' => $this->evidence_voice_memo,
            'evidence_attachments' => $this->evidence_attachments ?? [],
            'has_evidence' => $this->hasEvidence(),
        ];
    }

    /**
     * Evidence capture methods
     */
    public function addPhoto(string $photoPath): void
    {
        if (! $this->evidence_photos) {
            $this->evidence_photos = [];
        }
        $this->evidence_photos[] = $photoPath;
    }

    public function removePhoto(string $photoPath): void
    {
        if ($this->evidence_photos) {
            $this->evidence_photos = array_values(array_filter($this->evidence_photos, fn ($path) => $path !== $photoPath));
        }
    }

    public function addAttachment(string $attachmentPath): void
    {
        if (! $this->evidence_attachments) {
            $this->evidence_attachments = [];
        }
        $this->evidence_attachments[] = $attachmentPath;
    }

    public function removeAttachment(string $attachmentPath): void
    {
        if ($this->evidence_attachments) {
            $this->evidence_attachments = array_values(array_filter($this->evidence_attachments, fn ($path) => $path !== $attachmentPath));
        }
    }

    public function hasEvidence(): bool
    {
        return ! empty($this->evidence_notes) ||
               ! empty($this->evidence_photos) ||
               ! empty($this->evidence_voice_memo) ||
               ! empty($this->evidence_attachments);
    }

    public function getEvidenceCount(): int
    {
        $count = 0;
        if (! empty($this->evidence_notes)) {
            $count++;
        }
        if (! empty($this->evidence_photos)) {
            $count += count($this->evidence_photos);
        }
        if (! empty($this->evidence_voice_memo)) {
            $count++;
        }
        if (! empty($this->evidence_attachments)) {
            $count += count($this->evidence_attachments);
        }

        return $count;
    }

    /**
     * Get review for this session
     */
    public function review(SupabaseClient $supabase): ?Review
    {
        return Review::forSession($this->id, $supabase);
    }

    /**
     * Complete session with evidence
     */
    public function completeWithEvidence(
        ?string $notes,
        ?array $photos,
        ?string $voiceMemo,
        ?array $attachments,
        SupabaseClient $supabase
    ): bool {
        $this->status = 'done';
        $this->completed_at = Carbon::now();

        if ($notes) {
            $this->evidence_notes = $notes;
        }
        if ($photos) {
            $this->evidence_photos = $photos;
        }
        if ($voiceMemo) {
            $this->evidence_voice_memo = $voiceMemo;
        }
        if ($attachments) {
            $this->evidence_attachments = $attachments;
        }

        $success = $this->save($supabase);

        // Automatically create review when session is completed
        if ($success && $this->status === 'done' && $this->completed_at) {
            $existingReview = Review::forSession($this->id, $supabase);
            if (! $existingReview) {
                Review::createFromSession($this, $supabase);
            }
        }

        return $success;
    }
}
