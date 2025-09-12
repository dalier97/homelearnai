<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $unit_id
 * @property int $user_id
 * @property string $import_type
 * @property string $filename
 * @property string $status
 * @property int $total_cards
 * @property int $imported_cards
 * @property int $failed_cards
 * @property int $duplicate_cards
 * @property int $media_files
 * @property array|null $import_options
 * @property array|null $import_metadata
 * @property array|null $import_results
 * @property array|null $rollback_data
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FlashcardImport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'unit_id',
        'user_id',
        'import_type',
        'filename',
        'status',
        'total_cards',
        'imported_cards',
        'failed_cards',
        'duplicate_cards',
        'media_files',
        'import_options',
        'import_metadata',
        'import_results',
        'rollback_data',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'unit_id' => 'integer',
        'user_id' => 'integer',
        'total_cards' => 'integer',
        'imported_cards' => 'integer',
        'failed_cards' => 'integer',
        'duplicate_cards' => 'integer',
        'media_files' => 'integer',
        'import_options' => 'array',
        'import_metadata' => 'array',
        'import_results' => 'array',
        'rollback_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    /**
     * Import type constants
     */
    public const TYPE_ANKI = 'anki';

    public const TYPE_MNEMOSYNE = 'mnemosyne';

    public const TYPE_CSV = 'csv';

    public const TYPE_TSV = 'tsv';

    public const TYPE_TEXT = 'text';

    /**
     * Get the unit that owns the import.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user who created the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_ROLLED_BACK,
        ];
    }

    /**
     * Get all valid import types
     */
    public static function getImportTypes(): array
    {
        return [
            self::TYPE_ANKI,
            self::TYPE_MNEMOSYNE,
            self::TYPE_CSV,
            self::TYPE_TSV,
            self::TYPE_TEXT,
        ];
    }

    /**
     * Check if import is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if import failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if import can be rolled back
     */
    public function canRollback(): bool
    {
        return $this->status === self::STATUS_COMPLETED &&
               ! empty($this->rollback_data) &&
               $this->imported_cards > 0;
    }

    /**
     * Mark import as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed
     */
    public function markAsCompleted(array $results): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'import_results' => $results,
            'imported_cards' => $results['imported'] ?? 0,
            'failed_cards' => $results['failed'] ?? 0,
            'duplicate_cards' => $results['duplicates'] ?? 0,
        ]);
    }

    /**
     * Mark import as failed
     */
    public function markAsFailed(string $error): void
    {
        $results = $this->import_results ?? [];
        $results['error'] = $error;

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'import_results' => $results,
        ]);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_cards === 0) {
            return 0;
        }

        $processed = $this->imported_cards + $this->failed_cards;

        return min(100, (int) (($processed / $this->total_cards) * 100));
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): int
    {
        if ($this->imported_cards === 0 && $this->failed_cards === 0) {
            return 0;
        }

        $total = $this->imported_cards + $this->failed_cards;

        return (int) (($this->imported_cards / $total) * 100);
    }

    /**
     * Get duration in seconds
     */
    public function getDuration(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) round($this->started_at->diffInSeconds($this->completed_at));
    }

    /**
     * Get human-readable duration
     */
    public function getHumanDuration(): string
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return 'Unknown';
        }

        if ($duration < 60) {
            return $duration.' seconds';
        } elseif ($duration < 3600) {
            return floor($duration / 60).' minutes';
        } else {
            return floor($duration / 3600).' hours';
        }
    }

    /**
     * Get import summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'import_type' => $this->import_type,
            'status' => $this->status,
            'progress' => $this->getProgressPercentage(),
            'success_rate' => $this->getSuccessRate(),
            'duration' => $this->getHumanDuration(),
            'cards' => [
                'total' => $this->total_cards,
                'imported' => $this->imported_cards,
                'failed' => $this->failed_cards,
                'duplicates' => $this->duplicate_cards,
            ],
            'media_files' => $this->media_files,
            'can_rollback' => $this->canRollback(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Scope for user imports
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for unit imports
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Scope for completed imports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed imports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for recent imports (last 30 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }
}
