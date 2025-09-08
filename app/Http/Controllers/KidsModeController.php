<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\KidsModeAuditLog;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class KidsModeController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    /**
     * Enter kids mode for a specific child
     */
    public function enterKidsMode(Request $request, int $childId)
    {
        $request->validate([
            'child_id' => 'sometimes|integer',
        ]);

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        if (! $accessToken) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        // Configure SupabaseClient with user token
        $this->supabase->setUserToken($accessToken);

        // Verify the child exists and belongs to the user
        $child = Child::find((string) $childId, $this->supabase);
        if (! $child || $child->user_id !== $userId) {
            return response()->json(['error' => __('Child not found')], 404);
        }

        // Generate session security fingerprint
        $sessionFingerprint = $this->generateSessionFingerprint($request);

        // Set kids mode session data
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $childId);
        Session::put('kids_mode_child_name', $child->name);
        Session::put('kids_mode_entered_at', now()->toISOString());
        Session::put('kids_mode_fingerprint', $sessionFingerprint);

        // Log the entry event
        KidsModeAuditLog::logEvent(
            'enter',
            $userId,
            $childId,
            $request->ip(),
            $request->userAgent(),
            [
                'child_name' => $child->name,
                'session_fingerprint' => $sessionFingerprint,
                'entry_method' => $request->expectsJson() ? 'ajax' : 'direct',
            ]
        );

        \Log::info('Kids mode entered', [
            'user_id' => $userId,
            'child_id' => $childId,
            'child_name' => $child->name,
            'timestamp' => now()->toISOString(),
            'ip_address' => $request->ip(),
            'fingerprint' => substr($sessionFingerprint, 0, 8).'...',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Kids mode activated for :name', ['name' => $child->name]),
                'child_id' => $childId,
                'child_name' => $child->name,
            ]);
        }

        return redirect()->route('dashboard.child-today', ['child_id' => $childId])
            ->with('success', __('Kids mode activated for :name', ['name' => $child->name]));
    }

    /**
     * Show PIN entry screen to exit kids mode
     */
    public function showExitScreen(Request $request)
    {
        if (! Session::get('kids_mode_active')) {
            return redirect()->route('dashboard')->with('error', __('Kids mode is not active'));
        }

        $childId = Session::get('kids_mode_child_id');
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        if (! $accessToken) {
            return redirect()->route('login');
        }

        $this->supabase->setUserToken($accessToken);
        $child = Child::find((string) $childId, $this->supabase);

        // Check if PIN is set up
        $userPrefs = $this->getUserPreferences($userId);
        $hasPinSetup = ! empty($userPrefs['kids_mode_pin']);

        // Check if account is locked
        $isLocked = $this->isPinLocked($userPrefs);
        $lockoutTime = null;

        if ($isLocked && ! empty($userPrefs['kids_mode_pin_locked_until'])) {
            $lockoutTime = Carbon::parse($userPrefs['kids_mode_pin_locked_until']);
        }

        return view('kids-mode.exit', [
            'child' => $child,
            'has_pin_setup' => $hasPinSetup,
            'is_locked' => $isLocked,
            'lockout_time' => $lockoutTime,
            'attempts_remaining' => max(0, 5 - ($userPrefs['kids_mode_pin_attempts'] ?? 0)),
        ]);
    }

    /**
     * Validate PIN and exit kids mode
     */
    public function validateExitPin(Request $request)
    {
        if (! Session::get('kids_mode_active')) {
            KidsModeAuditLog::logEvent(
                'exit_failed',
                Session::get('user_id'),
                Session::get('kids_mode_child_id'),
                $request->ip(),
                $request->userAgent(),
                ['reason' => 'kids_mode_not_active']
            );

            return response()->json(['error' => __('Kids mode is not active')], 400);
        }

        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');
        $pin = $request->input('pin');
        $childId = Session::get('kids_mode_child_id');

        if (! $accessToken) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        // Validate session fingerprint for additional security
        if (! $this->validateSessionFingerprint($request)) {
            KidsModeAuditLog::logEvent(
                'exit_failed',
                $userId,
                $childId,
                $request->ip(),
                $request->userAgent(),
                ['reason' => 'session_fingerprint_mismatch']
            );

            // Force logout for security
            Session::flush();

            return response()->json(['error' => __('Security violation detected. Please login again.')], 403);
        }

        $this->supabase->setUserToken($accessToken);

        // Get user preferences
        $userPrefs = $this->getUserPreferences($userId);

        // Check if PIN is set up
        if (empty($userPrefs['kids_mode_pin'])) {
            return response()->json(['error' => __('PIN is not set up. Please set up a PIN first.')], 400);
        }

        // Enhanced rate limiting with progressive lockout and IP checks
        $ipAddress = $request->ip();
        $userAttempts = ($userPrefs['kids_mode_pin_attempts'] ?? 0);
        $ipFailedAttempts = KidsModeAuditLog::getFailedAttemptsByIP($ipAddress, 60);
        $userFailedAttempts = KidsModeAuditLog::getRecentFailedAttempts($userId, 60);

        // Check database-based lockout first
        if ($this->isPinLocked($userPrefs)) {
            $lockoutTime = Carbon::parse($userPrefs['kids_mode_pin_locked_until']);
            KidsModeAuditLog::logEvent('exit_blocked', $userId, $childId, $ipAddress, $request->userAgent(),
                ['reason' => 'database_lockout', 'lockout_until' => $lockoutTime->toISOString()]);

            return response()->json([
                'error' => __('Too many failed attempts. Try again after :time', ['time' => $lockoutTime->format('H:i')]),
                'locked_until' => $lockoutTime->toISOString(),
            ], 429);
        }

        // IP-based rate limiting (more aggressive)
        $ipRateLimitKey = 'kids-mode-ip-attempts:'.$ipAddress;
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 10)) { // 10 attempts per IP per hour
            $retryAfter = RateLimiter::availableIn($ipRateLimitKey);
            KidsModeAuditLog::logEvent('exit_blocked', $userId, $childId, $ipAddress, $request->userAgent(),
                ['reason' => 'ip_rate_limit', 'retry_after' => $retryAfter]);

            return response()->json([
                'error' => __('Too many attempts from this location. Please wait :minutes minutes.',
                    ['minutes' => ceil($retryAfter / 60)]),
                'retry_after' => $retryAfter,
            ], 429);
        }

        // User-based rate limiting
        $userRateLimitKey = 'kids-mode-pin-attempts:'.$userId;
        if (RateLimiter::tooManyAttempts($userRateLimitKey, 5)) {
            return response()->json([
                'error' => __('Too many attempts. Please wait before trying again.'),
                'retry_after' => RateLimiter::availableIn($userRateLimitKey),
            ], 429);
        }

        // Validate PIN
        $pinValid = Hash::check($pin, $userPrefs['kids_mode_pin']);

        if (! $pinValid) {
            // Increment failed attempts with progressive lockout
            $attempts = $userAttempts + 1;

            $updateData = [
                'kids_mode_pin_attempts' => $attempts,
            ];

            // Progressive lockout: 5min, 15min, 1hr, 24hr
            $lockoutMinutes = $this->calculateLockoutDuration($attempts);
            if ($attempts >= 5) {
                $lockoutUntil = now()->addMinutes($lockoutMinutes);
                $updateData['kids_mode_pin_locked_until'] = $lockoutUntil->toISOString();
            }

            // Update user preferences
            $this->updateUserPreferences($userId, $updateData);

            // Track rate limiting for both user and IP
            RateLimiter::hit($userRateLimitKey);
            RateLimiter::hit($ipRateLimitKey);

            // Log failed attempt
            KidsModeAuditLog::logEvent(
                'pin_failed',
                $userId,
                $childId,
                $ipAddress,
                $request->userAgent(),
                [
                    'attempts' => $attempts,
                    'locked' => $attempts >= 5,
                    'lockout_minutes' => $lockoutMinutes,
                    'user_failed_attempts_1h' => $userFailedAttempts + 1,
                    'ip_failed_attempts_1h' => $ipFailedAttempts + 1,
                ]
            );

            \Log::warning('Kids mode PIN validation failed', [
                'user_id' => $userId,
                'child_id' => $childId,
                'attempts' => $attempts,
                'ip_address' => $ipAddress,
                'locked' => $attempts >= 5,
                'lockout_minutes' => $lockoutMinutes,
            ]);

            return response()->json([
                'error' => __('Incorrect PIN. :attempts attempts remaining.', [
                    'attempts' => max(0, 5 - $attempts),
                ]),
                'attempts_remaining' => max(0, 5 - $attempts),
                'locked' => $attempts >= 5,
                'lockout_minutes' => $lockoutMinutes,
            ], 400);
        }

        // PIN is valid - clear failed attempts and exit kids mode
        $this->updateUserPreferences($userId, [
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ]);

        // Clear rate limiting for both user and IP
        RateLimiter::clear($userRateLimitKey);
        RateLimiter::clear($ipRateLimitKey);

        // Calculate session duration for audit
        $enteredAt = Session::get('kids_mode_entered_at');
        $sessionDuration = $enteredAt ? Carbon::parse($enteredAt)->diffInMinutes(now()) : null;

        // Log successful exit
        KidsModeAuditLog::logEvent(
            'exit_success',
            $userId,
            $childId,
            $ipAddress,
            $request->userAgent(),
            [
                'session_duration_minutes' => $sessionDuration,
                'previous_failed_attempts' => $userAttempts,
                'exit_method' => 'pin_validated',
            ]
        );

        // Clear kids mode session data including fingerprint
        Session::forget(['kids_mode_active', 'kids_mode_child_id', 'kids_mode_child_name', 'kids_mode_entered_at', 'kids_mode_fingerprint']);

        \Log::info('Kids mode exited successfully', [
            'user_id' => $userId,
            'child_id' => $childId,
            'ip_address' => $ipAddress,
            'session_duration_minutes' => $sessionDuration,
            'timestamp' => now()->toISOString(),
        ]);

        return response()->json([
            'message' => __('Kids mode deactivated successfully'),
            'redirect_url' => route('dashboard'),
        ]);
    }

    /**
     * Show PIN settings page for parents
     */
    public function showPinSettings(Request $request)
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        if (! $accessToken) {
            return redirect()->route('login');
        }

        $this->supabase->setUserToken($accessToken);
        $userPrefs = $this->getUserPreferences($userId);

        $hasPinSetup = ! empty($userPrefs['kids_mode_pin']);
        $isLocked = $this->isPinLocked($userPrefs);
        $lockoutTime = null;

        if ($isLocked && ! empty($userPrefs['kids_mode_pin_locked_until'])) {
            $lockoutTime = Carbon::parse($userPrefs['kids_mode_pin_locked_until']);
        }

        return view('kids-mode.pin-settings', [
            'has_pin_setup' => $hasPinSetup,
            'is_locked' => $isLocked,
            'lockout_time' => $lockoutTime,
            'failed_attempts' => $userPrefs['kids_mode_pin_attempts'] ?? 0,
        ]);
    }

    /**
     * Set or update the kids mode PIN
     */
    public function updatePin(Request $request)
    {
        try {
            $request->validate([
                'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
                'pin_confirmation' => 'required|same:pin',
            ], [
                'pin.required' => __('PIN is required'),
                'pin.size' => __('PIN must be exactly 4 digits'),
                'pin.regex' => __('PIN must be exactly 4 digits'),
                'pin_confirmation.required' => __('PIN confirmation is required'),
                'pin_confirmation.same' => __('PIN confirmation does not match'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // For HTMX requests, return HTML error message
            if ($request->header('HX-Request')) {
                $errors = $e->errors();
                $errorMessages = [];
                foreach ($errors as $field => $messages) {
                    foreach ($messages as $message) {
                        $errorMessages[] = $message;
                    }
                }
                $errorHtml = '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">';
                $errorHtml .= '<div class="flex items-start">';
                $errorHtml .= '<svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">';
                $errorHtml .= '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
                $errorHtml .= '</svg>';
                $errorHtml .= '<div>';
                foreach ($errorMessages as $message) {
                    $errorHtml .= '<p class="text-red-800">'.e($message).'</p>';
                }
                $errorHtml .= '</div>';
                $errorHtml .= '</div>';
                $errorHtml .= '</div>';

                return response($errorHtml, 422);
            }
            throw $e;
        }

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');
        $pin = $request->input('pin');

        if (! $accessToken) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        $this->supabase->setUserToken($accessToken);

        // Hash PIN directly (Laravel's Hash::make already includes salt)
        $hashedPin = Hash::make($pin);

        // Update user preferences with new PIN
        $updateData = [
            'kids_mode_pin' => $hashedPin,
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ];

        $success = $this->updateUserPreferences($userId, $updateData);

        if (! $success) {
            \Log::error('PIN update failed - database operation unsuccessful', [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
            ]);

            if ($request->header('HX-Request')) {
                $errorHtml = '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">';
                $errorHtml .= '<div class="flex items-start">';
                $errorHtml .= '<svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">';
                $errorHtml .= '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
                $errorHtml .= '</svg>';
                $errorHtml .= '<div>';
                $errorHtml .= '<p class="text-red-800">'.__('Failed to save PIN. Please try again.').'</p>';
                $errorHtml .= '</div>';
                $errorHtml .= '</div>';
                $errorHtml .= '</div>';

                return response($errorHtml, 500);
            }

            if ($request->expectsJson()) {
                return response()->json(['error' => __('Failed to save PIN. Please try again.')], 500);
            }

            return back()->withErrors(['pin' => __('Failed to save PIN. Please try again.')]);
        }

        // Log PIN update event
        KidsModeAuditLog::logEvent(
            'pin_updated',
            $userId,
            null,
            $request->ip(),
            $request->userAgent(),
            [
                'action' => 'pin_set_or_updated',
                'method' => $request->expectsJson() ? 'ajax' : 'direct',
            ]
        );

        \Log::info('Kids mode PIN updated', [
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        // For HTMX requests, return HTML success message
        if ($request->header('HX-Request')) {
            $successHtml = '<div class="p-4 bg-green-50 border border-green-200 rounded-lg">';
            $successHtml .= '<div class="flex items-center">';
            $successHtml .= '<svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">';
            $successHtml .= '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>';
            $successHtml .= '</svg>';
            $successHtml .= '<span class="text-green-800 font-medium">'.__('Kids mode PIN has been set successfully').'</span>';
            $successHtml .= '</div>';
            $successHtml .= '</div>';

            // Also trigger a refresh after success via HX-Trigger header
            return response($successHtml)
                ->header('HX-Trigger', 'pinUpdated');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Kids mode PIN has been set successfully'),
            ]);
        }

        return redirect()->route('kids-mode.settings')
            ->with('success', __('Kids mode PIN has been set successfully'));
    }

    /**
     * Reset (clear) the kids mode PIN
     */
    public function resetPin(Request $request)
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        if (! $accessToken) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        $this->supabase->setUserToken($accessToken);

        // Clear PIN-related fields
        $updateData = [
            'kids_mode_pin' => null,
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ];

        $this->updateUserPreferences($userId, $updateData);

        // Log PIN reset event
        KidsModeAuditLog::logEvent(
            'pin_reset',
            $userId,
            null,
            $request->ip(),
            $request->userAgent(),
            [
                'action' => 'pin_cleared',
                'method' => $request->expectsJson() ? 'ajax' : 'direct',
            ]
        );

        \Log::info('Kids mode PIN reset', [
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Kids mode PIN has been reset successfully'),
            ]);
        }

        return redirect()->route('kids-mode.settings')
            ->with('success', __('Kids mode PIN has been reset successfully'));
    }

    /**
     * Get user preferences from database
     */
    private function getUserPreferences(string $userId): array
    {
        try {
            $preferences = $this->supabase->from('user_preferences')
                ->select('kids_mode_pin, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->eq('user_id', $userId)
                ->single();

            return $preferences ?? [];
        } catch (\Exception $e) {
            \Log::error('Failed to fetch user preferences for kids mode', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Update user preferences in database
     */
    private function updateUserPreferences(string $userId, array $data): bool
    {
        try {
            \Log::info('Updating user preferences for kids mode', [
                'user_id' => $userId,
                'data' => $data,
            ]);

            // Check if user preferences exist
            $existing = $this->supabase->from('user_preferences')
                ->select('id')
                ->eq('user_id', $userId)
                ->single();

            $result = null;
            $updateData = array_merge($data, [
                'updated_at' => now()->toISOString(),
            ]);

            if ($existing) {
                \Log::info('Updating existing user preferences record', [
                    'user_id' => $userId,
                    'existing_id' => $existing['id'],
                ]);

                // Update existing preferences
                $result = $this->supabase->from('user_preferences')
                    ->eq('user_id', $userId)
                    ->update($updateData);
            } else {
                \Log::info('Creating new user preferences record', [
                    'user_id' => $userId,
                ]);

                // Create new preferences record
                $result = $this->supabase->from('user_preferences')
                    ->insert(array_merge($updateData, [
                        'user_id' => $userId,
                    ]));
            }

            \Log::info('User preferences operation completed', [
                'user_id' => $userId,
                'operation' => $existing ? 'update' : 'insert',
                'result' => $result,
                'result_count' => count($result),
            ]);

            // Verify the operation was successful
            if (count($result) > 0) {
                \Log::info('User preferences updated successfully', [
                    'user_id' => $userId,
                ]);

                return true;
            } else {
                \Log::warning('User preferences operation returned empty result', [
                    'user_id' => $userId,
                    'result' => $result,
                ]);

                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update user preferences for kids mode', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return false;
        }
    }

    /**
     * Check if PIN is currently locked due to failed attempts
     */
    private function isPinLocked(array $userPrefs): bool
    {
        if (empty($userPrefs['kids_mode_pin_locked_until'])) {
            return false;
        }

        try {
            $lockedUntil = Carbon::parse($userPrefs['kids_mode_pin_locked_until']);

            return now()->lt($lockedUntil);
        } catch (\Exception $e) {
            \Log::warning('Invalid lockout timestamp in user preferences', [
                'timestamp' => $userPrefs['kids_mode_pin_locked_until'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate session fingerprint for enhanced security
     */
    private function generateSessionFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent() ?? '',
            $request->header('Accept-Language') ?? '',
            $request->header('Accept-Encoding') ?? '',
            $request->ip(),
            Session::getId(),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Validate session fingerprint to detect session hijacking
     */
    private function validateSessionFingerprint(Request $request): bool
    {
        $storedFingerprint = Session::get('kids_mode_fingerprint');
        if (! $storedFingerprint) {
            // No fingerprint stored, allow for backward compatibility
            return true;
        }

        $currentFingerprint = $this->generateSessionFingerprint($request);

        return hash_equals($storedFingerprint, $currentFingerprint);
    }

    /**
     * Calculate progressive lockout duration based on failed attempts
     */
    private function calculateLockoutDuration(int $attempts): int
    {
        return match (true) {
            $attempts >= 20 => 1440,  // 24 hours
            $attempts >= 15 => 360,   // 6 hours
            $attempts >= 10 => 60,    // 1 hour
            $attempts >= 7 => 15,     // 15 minutes
            $attempts >= 5 => 5,      // 5 minutes
            default => 0
        };
    }
}
