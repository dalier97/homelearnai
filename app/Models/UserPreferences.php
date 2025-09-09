<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreferences extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'locale',
        'timezone',
        'date_format',
        'onboarding_completed',
        'onboarding_skipped',
        'kids_mode_pin',
        'kids_mode_pin_salt',
        'kids_mode_pin_attempts',
        'kids_mode_pin_locked_until',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'onboarding_completed' => 'boolean',
        'onboarding_skipped' => 'boolean',
        'kids_mode_pin_attempts' => 'integer',
        'kids_mode_pin_locked_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'locale' => 'en',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'onboarding_completed' => false,
        'onboarding_skipped' => false,
        'kids_mode_pin_attempts' => 0,
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if kids mode PIN is set up
     */
    public function hasPinSetup(): bool
    {
        return ! empty($this->kids_mode_pin);
    }

    /**
     * Check if PIN is currently locked due to failed attempts
     */
    public function isPinLocked(): bool
    {
        if (empty($this->kids_mode_pin_locked_until)) {
            return false;
        }

        return now()->lt($this->kids_mode_pin_locked_until);
    }

    /**
     * Get remaining PIN attempts
     */
    public function getRemainingAttempts(): int
    {
        return max(0, 5 - $this->kids_mode_pin_attempts);
    }

    /**
     * Reset PIN attempts and unlock
     */
    public function resetPinAttempts(): void
    {
        $this->update([
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ]);
    }

    /**
     * Increment PIN attempts with progressive lockout
     */
    public function incrementPinAttempts(): void
    {
        $attempts = $this->kids_mode_pin_attempts + 1;

        $updateData = [
            'kids_mode_pin_attempts' => $attempts,
        ];

        // Progressive lockout: 5min, 15min, 1hr, 24hr
        if ($attempts >= 5) {
            $lockoutMinutes = match ($attempts) {
                5 => 5,
                6 => 15,
                7 => 60,
                default => 1440, // 24 hours
            };

            $updateData['kids_mode_pin_locked_until'] = now()->addMinutes($lockoutMinutes);
        }

        $this->update($updateData);
    }
}
