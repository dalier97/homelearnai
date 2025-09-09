<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Get locale preference from cookie or session
        $locale = $this->getPreferredLocale($request);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'locale' => $locale,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Set session locale to match user preference
        Session::put('locale', $locale);

        // Clear locale cookies since we've stored them in user record
        $this->clearLocaleCookies();

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Get preferred locale from cookies or default
     */
    private function getPreferredLocale(Request $request): string
    {
        // Check for post-logout locale cookie first (most recent preference)
        if ($request->hasCookie('post_logout_locale')) {
            $cookieLocale = $request->cookie('post_logout_locale');
            if (in_array($cookieLocale, ['en', 'ru'])) {
                return $cookieLocale;
            }
        }

        // Fallback to pre-auth locale cookie
        if ($request->hasCookie('pre_auth_locale')) {
            $cookieLocale = $request->cookie('pre_auth_locale');
            if (in_array($cookieLocale, ['en', 'ru'])) {
                return $cookieLocale;
            }
        }

        // Use session locale or default
        return session('locale', config('app.locale', 'en'));
    }

    /**
     * Clear locale cookies after storing in user record
     */
    private function clearLocaleCookies(): void
    {
        \Cookie::queue(\Cookie::forget('pre_auth_locale'));
        \Cookie::queue(\Cookie::forget('post_logout_locale'));
    }
}
