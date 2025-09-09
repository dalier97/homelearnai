<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Set session locale from user's preference
        if (Auth::user()->locale) {
            Session::put('locale', Auth::user()->locale);
        } else {
            // Update user locale if they have cookie preferences
            $this->updateUserLocaleFromCookies($request);
        }

        // Clear locale cookies since user is authenticated
        $this->clearLocaleCookies();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Preserve user's locale preference before logout
        $currentLocale = session('locale', config('app.locale', 'en'));

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Restore locale in session for seamless experience
        Session::put('locale', $currentLocale);

        // Set a cookie backup for extra reliability
        $response = redirect()->route('login');
        $response->cookie('post_logout_locale', $currentLocale, 60 * 24 * 7); // 7 days

        return $response;
    }

    /**
     * Update user locale from cookies if available
     */
    private function updateUserLocaleFromCookies(Request $request): void
    {
        $cookieLocale = null;

        // Check for post-logout locale cookie first (most recent preference)
        if ($request->hasCookie('post_logout_locale')) {
            $cookieLocale = $request->cookie('post_logout_locale');
        }

        // Fallback to pre-auth locale cookie if no post-logout cookie
        if (empty($cookieLocale) && $request->hasCookie('pre_auth_locale')) {
            $cookieLocale = $request->cookie('pre_auth_locale');
        }

        if ($cookieLocale && in_array($cookieLocale, ['en', 'ru'])) {
            // Update user's locale preference in database
            Auth::user()->update(['locale' => $cookieLocale]);

            // Set session locale
            Session::put('locale', $cookieLocale);
        } elseif (Auth::user()->locale) {
            // Use user's stored locale preference
            Session::put('locale', Auth::user()->locale);
        }
    }

    /**
     * Clear locale cookies after authentication
     */
    private function clearLocaleCookies(): void
    {
        \Cookie::queue(\Cookie::forget('pre_auth_locale'));
        \Cookie::queue(\Cookie::forget('post_logout_locale'));
    }
}
