<?php

namespace App\Models;

use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class KidsModeAuditLog extends Model
{
    protected $table = 'kids_mode_audit_logs';

    protected $fillable = [
        'user_id',
        'child_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Log a kids mode security event
     */
    public static function logEvent(
        string $action,
        string $userId,
        ?int $childId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = []
    ): void {
        try {
            // Use Supabase to insert the log
            $supabase = app(SupabaseClient::class);

            // Note: For audit logs, we might need service-level access
            // For now, we'll use the existing client setup
            // In production, this should use a service account key

            $logData = [
                'user_id' => $userId,
                'child_id' => $childId,
                'action' => $action,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ];

            $supabase->from('kids_mode_audit_logs')->insert($logData);

            Log::info('Kids mode audit log created', [
                'action' => $action,
                'user_id' => $userId,
                'child_id' => $childId,
                'ip_address' => $ipAddress,
            ]);

        } catch (\Exception $e) {
            // Don't let audit logging failures affect the main functionality
            Log::error('Failed to create kids mode audit log', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get audit logs for a user with pagination
     */
    public static function forUser(string $userId, int $limit = 50): array
    {
        try {
            $supabase = app(SupabaseClient::class);
            // Use system-level access for audit logs

            return $supabase->from('kids_mode_audit_logs')
                ->select('*')
                ->eq('user_id', $userId)
                ->order('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to fetch kids mode audit logs', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get recent failed PIN attempts for rate limiting analysis
     */
    public static function getRecentFailedAttempts(string $userId, int $minutes = 60): int
    {
        try {
            $supabase = app(SupabaseClient::class);
            // Use system-level access for audit logs

            $since = Carbon::now()->subMinutes($minutes)->toISOString();

            $logs = $supabase->from('kids_mode_audit_logs')
                ->select('id')
                ->eq('user_id', $userId)
                ->eq('action', 'pin_failed')
                ->gte('created_at', $since)
                ->get();

            return $logs->count();
        } catch (\Exception $e) {
            Log::error('Failed to fetch recent failed attempts', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get failed attempts by IP address for additional rate limiting
     */
    public static function getFailedAttemptsByIP(string $ipAddress, int $minutes = 60): int
    {
        try {
            $supabase = app(SupabaseClient::class);
            // Use system-level access for audit logs

            $since = Carbon::now()->subMinutes($minutes)->toISOString();

            $logs = $supabase->from('kids_mode_audit_logs')
                ->select('id')
                ->eq('ip_address', $ipAddress)
                ->eq('action', 'pin_failed')
                ->gte('created_at', $since)
                ->get();

            return $logs->count();
        } catch (\Exception $e) {
            Log::error('Failed to fetch failed attempts by IP', [
                'ip_address' => $ipAddress,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
