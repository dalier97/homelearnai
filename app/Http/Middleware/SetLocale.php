<?php

namespace App\Http\Middleware;

use App\Services\SupabaseClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private SupabaseClient $supabase;

    public function __construct(SupabaseClient $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request.
     *
     * Locale detection priority:
     * 1. User's saved preference (if authenticated)
     * 2. Session locale
     * 3. Default application locale
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Validate locale is supported
        $availableLocales = array_keys(config('app.available_locales', ['en']));
        if (! in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Set the application locale
        App::setLocale($locale);

        // Store in session for all users (safely, session might not be available)
        try {
            session()->put('locale', $locale);
        } catch (\Exception $e) {
            // Session not available, skip session storage
        }

        // Debug logging for E2E tests
        if (config('app.env') === 'testing') {
            $debugUserId = null;
            $debugGuestId = null;
            $sessionLocale = null;
            $sessionId = null;

            try {
                $debugUserId = session('user_id');
                $debugGuestId = ! $debugUserId ? $this->generateGuestId($request) : null;
                $sessionLocale = session('locale');
                $sessionId = session()->getId();
            } catch (\Exception $e) {
                // Session not available
            }

            \Log::info('SetLocale: Middleware executed', [
                'determined_locale' => $locale,
                'user_type' => $debugUserId ? 'authenticated' : 'guest',
                'user_id' => $debugUserId,
                'guest_id' => $debugGuestId,
                'session_locale' => $sessionLocale ?? 'session unavailable',
                'backup_cookie_locale' => $request->cookie('locale_backup'),
                'backup_cookie_decrypted' => $this->decryptCookie($request, 'locale_backup'),
                'app_locale' => App::getLocale(),
                'session_id' => $sessionId ?? 'session unavailable',
            ]);
        }

        return $next($request);
    }

    /**
     * Generate a consistent guest ID for non-authenticated users
     * Must match the logic in LocaleController EXACTLY
     */
    private function generateGuestId(Request $request): string
    {
        // Priority 1: Use existing guest_id from Laravel session if available
        try {
            if (session()->has('guest_id')) {
                return session()->get('guest_id');
            }
        } catch (\Exception $e) {
            // Session not available, continue to fingerprint method
        }

        // Priority 2: Create guest ID from request fingerprint (exactly like LocaleController)
        $userAgent = $request->header('User-Agent', 'unknown');
        $acceptLanguage = $request->header('Accept-Language', 'unknown');

        // For E2E tests where sessions change, use a more stable fingerprint
        // that doesn't depend on the session ID
        $ipAddress = $request->ip() ?? 'unknown';
        $fingerprint = hash('sha256', $userAgent.$acceptLanguage.$ipAddress);
        $guestId = 'guest_'.substr($fingerprint, 0, 16);

        // Store in Laravel session for consistency
        try {
            session()->put('guest_id', $guestId);
        } catch (\Exception $e) {
            // Session not available, guest ID will be regenerated next time
        }

        return $guestId;
    }

    // Removed caching methods - not compatible with multi-process/load-balanced environments
    // Always read from database for consistency across all servers

    /**
     * Determine the locale to use for this request
     * Enhanced with pre-authentication cookie support for auth pages
     */
    /**
     * Decrypt a Laravel cookie value
     */
    private function decryptCookie(Request $request, string $cookieName): ?string
    {
        try {
            if (! $request->hasCookie($cookieName)) {
                return null;
            }

            $cookieValue = $request->cookie($cookieName);
            if (empty($cookieValue)) {
                return null;
            }

            // Try to decrypt the cookie using Laravel's encryption
            $decrypted = decrypt($cookieValue);
            if (is_string($decrypted) && ! empty($decrypted)) {
                return $decrypted;
            }

            // If decryption returns non-string or empty, try the raw value
            return $cookieValue;
        } catch (\Exception $e) {
            // Cookie decryption failed, return null to skip this option
            \Log::debug('SetLocale: Cookie decryption failed', [
                'cookie_name' => $cookieName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Determine the locale to use for this request
     * Enhanced with pre-authentication cookie support for auth pages
     */
    private function determineLocale(Request $request): string
    {
        $userId = null;
        try {
            $userId = session('user_id');
        } catch (\Exception $e) {
            // Session not available
        }
        $isAuthenticated = ! empty($userId);
        $isAuthPage = $this->isAuthenticationPage($request);

        // 1. Check database for authenticated user's preference
        if ($isAuthenticated) {
            try {
                // Always read from database - no caching for multi-process compatibility
                $accessToken = session('supabase_token');
                if ($accessToken) {
                    $this->supabase->setUserToken($accessToken);

                    $userPrefs = $this->supabase->from('user_preferences')
                        ->select('locale')
                        ->eq('user_id', $userId)
                        ->single();

                    if ($userPrefs && ! empty($userPrefs['locale'])) {
                        $locale = $userPrefs['locale'];

                        return $locale;
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('Failed to fetch user locale preference', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // 2. Check database for guest user's preference
            try {
                $guestId = $this->generateGuestId($request);

                // Always read from database - no caching for multi-process compatibility
                $guestPrefs = $this->supabase->from('guest_preferences')
                    ->select('locale')
                    ->eq('guest_id', $guestId)
                    ->single();

                if ($guestPrefs && ! empty($guestPrefs['locale'])) {
                    $locale = $guestPrefs['locale'];

                    return $locale;
                }
            } catch (\Exception $e) {
                \Log::debug('Failed to fetch guest locale preference', [
                    'guest_id' => $this->generateGuestId($request),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Check session (safely, session might not be available in all contexts)
        try {
            $sessionLocale = session('locale');
            if (! empty($sessionLocale)) {
                return $sessionLocale;
            }
        } catch (\Exception $e) {
            // Session not available, continue to next option
        }

        // 4. Check pre-auth cookie for login/register pages (before authentication)
        if ($isAuthPage && $request->hasCookie('pre_auth_locale')) {
            $cookieLocale = $request->cookie('pre_auth_locale');
            if (! empty($cookieLocale)) {
                \Log::debug('SetLocale: Using pre-auth cookie locale', [
                    'locale' => $cookieLocale,
                    'route' => $request->route()->getName(),
                ]);

                return $cookieLocale;
            }
        }

        // 4.5. Check post-logout cookie for preserving language after logout
        if ($request->hasCookie('post_logout_locale')) {
            $cookieLocale = $request->cookie('post_logout_locale');
            if (! empty($cookieLocale)) {
                \Log::debug('SetLocale: Using post-logout cookie locale', [
                    'locale' => $cookieLocale,
                    'route' => $request->route() ? $request->route()->getName() : 'unknown',
                ]);

                return $cookieLocale;
            }
        }

        // 5. Check backup cookie (for testing environment when sessions fail)
        if (config('app.env') === 'testing' && $request->hasCookie('locale_backup')) {
            $cookieLocale = $this->decryptCookie($request, 'locale_backup');
            if (! empty($cookieLocale)) {
                return $cookieLocale;
            }
        }

        // 6. Use default application locale
        return config('app.locale', 'en');
    }

    /**
     * Check if the current request is for an authentication page
     */
    private function isAuthenticationPage(Request $request): bool
    {
        $route = $request->route();
        if (! $route) {
            return false;
        }

        $routeName = $route->getName();
        $authRoutes = ['login', 'register', 'auth.confirm'];

        return in_array($routeName, $authRoutes);
    }
}
