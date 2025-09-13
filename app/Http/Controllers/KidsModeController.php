<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\KidsModeAuditLog;
use App\Models\User;
use App\Models\UserPreferences;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class KidsModeController extends Controller
{
    /**
     * Enter kids mode for a specific child
     */
    public function enterKidsMode(Request $request, int $childId)
    {
        $request->validate([
            'child_id' => 'sometimes|integer',
        ]);

        $userId = auth()->id();

        // Verify the child exists and belongs to the user using Eloquent
        $child = Child::where('id', $childId)
            ->where('user_id', $userId)
            ->first();

        if (! $child) {
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

        // Log the security event
        KidsModeAuditLog::logEvent(
            'enter',
            (string) $userId,
            $childId,
            $request->ip(),
            $request->userAgent(),
            [
                'child_name' => $child->name,
                'fingerprint' => substr($sessionFingerprint, 0, 8).'...',
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
        $userId = auth()->id();

        $child = Child::where('id', $childId)
            ->where('user_id', $userId)
            ->first();

        // Get user preferences directly from database to avoid caching issues
        $userPrefs = UserPreferences::where('user_id', $userId)->first();
        if (! $userPrefs) {
            $userPrefs = UserPreferences::create([
                'user_id' => $userId,
                'kids_mode_pin' => null,
                'kids_mode_pin_salt' => null,
                'kids_mode_pin_attempts' => 0,
                'kids_mode_pin_locked_until' => null,
            ]);
        }

        // Check PIN setup and lock status
        $hasPinSetup = $userPrefs->hasPinSetup();
        $isLocked = $userPrefs->isPinLocked();
        $lockoutTime = $userPrefs->kids_mode_pin_locked_until;

        return view('kids-mode.exit', [
            'child' => $child,
            'has_pin_setup' => $hasPinSetup,
            'is_locked' => $isLocked,
            'lockout_time' => $lockoutTime,
            'attempts_remaining' => $userPrefs->getRemainingAttempts(),
        ]);
    }

    /**
     * Validate PIN and exit kids mode
     */
    public function validateExitPin(Request $request)
    {
        if (! Session::get('kids_mode_active')) {
            return response()->json(['error' => __('Kids mode is not active')], 400);
        }

        $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ]);

        $userId = auth()->id();
        $pin = $request->input('pin');
        $childId = Session::get('kids_mode_child_id');

        if (! $userId) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        // Validate session fingerprint for additional security
        if (! $this->validateSessionFingerprint($request)) {
            // Force logout for security
            Session::flush();

            return response()->json(['error' => __('Security violation detected. Please login again.')], 403);
        }

        // Get user preferences using Eloquent
        $user = User::findOrFail($userId);
        $userPrefs = $user->getPreferences();

        // Check if PIN is set up
        if (! $userPrefs->hasPinSetup()) {
            return response()->json(['error' => __('PIN is not set up. Please set up a PIN first.')], 400);
        }

        // Enhanced rate limiting with progressive lockout and IP checks
        $ipAddress = $request->ip();
        $userAttempts = $userPrefs->kids_mode_pin_attempts;

        // Check database-based lockout first
        if ($userPrefs->isPinLocked()) {
            $lockoutTime = $userPrefs->kids_mode_pin_locked_until;

            return response()->json([
                'error' => __('Too many failed attempts. Try again after :time', ['time' => $lockoutTime->format('H:i')]),
                'locked_until' => $lockoutTime->toISOString(),
            ], 429);
        }

        // IP-based rate limiting (more aggressive)
        $ipRateLimitKey = 'kids-mode-ip-attempts:'.$ipAddress;
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 10)) { // 10 attempts per IP per hour
            $retryAfter = RateLimiter::availableIn($ipRateLimitKey);

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
        $pinValid = Hash::check($pin, $userPrefs->kids_mode_pin);

        if (! $pinValid) {
            // Increment failed attempts with progressive lockout using Eloquent
            $userPrefs->incrementPinAttempts();
            $attempts = $userPrefs->kids_mode_pin_attempts;

            // Track rate limiting for both user and IP
            RateLimiter::hit($userRateLimitKey);
            RateLimiter::hit($ipRateLimitKey);

            \Log::warning('Kids mode PIN validation failed', [
                'user_id' => $userId,
                'child_id' => $childId,
                'attempts' => $attempts,
                'ip_address' => $ipAddress,
                'locked' => $attempts >= 5,
            ]);

            return response()->json([
                'error' => __('Incorrect PIN. :attempts attempts remaining.', [
                    'attempts' => $userPrefs->getRemainingAttempts(),
                ]),
                'attempts_remaining' => $userPrefs->getRemainingAttempts(),
                'locked' => $attempts >= 5,
                'lockout_minutes' => $this->calculateLockoutDuration($attempts),
            ], 400);
        }

        // PIN is valid - clear failed attempts and exit kids mode
        $userPrefs->resetPinAttempts();

        // Clear rate limiting for both user and IP
        RateLimiter::clear($userRateLimitKey);
        RateLimiter::clear($ipRateLimitKey);

        // Calculate session duration for audit
        $enteredAt = Session::get('kids_mode_entered_at');
        $sessionDuration = $enteredAt ? Carbon::parse($enteredAt)->diffInMinutes(now()) : null;

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
        $userId = auth()->id();

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $userPrefs = $user->getPreferences();

        $hasPinSetup = $userPrefs->hasPinSetup();
        $isLocked = $userPrefs->isPinLocked();
        $lockoutTime = $userPrefs->kids_mode_pin_locked_until;

        return view('kids-mode.pin-settings', [
            'has_pin_setup' => $hasPinSetup,
            'is_locked' => $isLocked,
            'lockout_time' => $lockoutTime,
            'failed_attempts' => $userPrefs->kids_mode_pin_attempts,
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

        $userId = auth()->id();
        $pin = $request->input('pin');

        if (! $userId) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        $user = User::findOrFail($userId);
        $userPrefs = $user->getPreferences();

        // Hash PIN and update preferences using Eloquent
        $hashedPin = Hash::make($pin);

        $success = $userPrefs->update([
            'kids_mode_pin' => $hashedPin,
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ]);

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
        $userId = auth()->id();

        if (! $userId) {
            return response()->json(['error' => __('Authentication required')], 401);
        }

        $user = User::findOrFail($userId);
        $userPrefs = $user->getPreferences();

        // Clear PIN-related fields using Eloquent
        $userPrefs->update([
            'kids_mode_pin' => null,
            'kids_mode_pin_attempts' => 0,
            'kids_mode_pin_locked_until' => null,
        ]);

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
     * Calculate lockout duration based on number of failed attempts
     */
    private function calculateLockoutDuration(int $attempts): int
    {
        return match ($attempts) {
            5 => 5,      // 5 minutes
            6 => 15,     // 15 minutes
            7 => 60,     // 1 hour
            default => 1440, // 24 hours
        };
    }

    /**
     * Generate session security fingerprint
     */
    private function generateSessionFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->ip(),
            session()->getId(),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Validate session fingerprint to prevent session hijacking
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
}
