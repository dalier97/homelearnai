<?php

namespace App\Http\Controllers;

use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->supabase->signIn($validated['email'], $validated['password']);

        \Log::info('Login attempt', [
            'email' => $validated['email'],
            'result' => $result,
        ]);

        if ($result && isset($result['access_token'])) {
            // Store token in session
            Session::put('supabase_token', $result['access_token']);
            Session::put('user', $result['user']);

            // Store user info in session
            Session::put('user_id', $result['user']['id']);

            // Migrate pre-auth locale cookie to database
            $this->migratePreAuthLocale($request, $result['user']['id']);

            // Ensure user has locale preference in database
            $this->ensureUserLocalePreference($request, $result['user']['id'], $result['access_token']);

            return redirect()->route('dashboard');
        }

        // Check for specific error codes
        if ($result && isset($result['error'])) {
            $errorMessage = $result['error'];

            // Add more helpful messages based on error
            if (isset($result['details']['error_code'])) {
                switch ($result['details']['error_code']) {
                    case 'invalid_credentials':
                        $errorMessage = __('Invalid email or password. If you just registered, please check your email to confirm your account first.');
                        break;
                    case 'email_not_confirmed':
                        $errorMessage = __('Please confirm your email address before logging in. Check your inbox for the confirmation email.');
                        break;
                    default:
                        $errorMessage = $result['error'];
                }
            }

            return back()->withErrors(['email' => $errorMessage]);
        }

        return back()->withErrors(['email' => __('Unable to log in. Please try again.')]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'name' => 'required|string|max:255',
        ]);

        $result = $this->supabase->signUp(
            $validated['email'],
            $validated['password'],
            ['name' => $validated['name']]
        );

        // Debug logging
        \Log::info('Supabase registration attempt', [
            'email' => $validated['email'],
            'result' => $result,
        ]);

        // Check for errors first (but treat warnings specially)
        if ($result && isset($result['error']) && ! isset($result['warning'])) {
            return back()->withErrors(['email' => $result['error']]);
        }

        // If we have a warning (like "Database error finding user"), try to login anyway
        // as the user might have been created despite the error
        if ($result && isset($result['warning'])) {
            \Log::info('Registration had warning, attempting login', [
                'email' => $validated['email'],
                'warning' => $result['warning'],
            ]);

            // Try to sign in with the credentials
            $loginResult = $this->supabase->signIn($validated['email'], $validated['password']);

            if ($loginResult && isset($loginResult['access_token'])) {
                Session::put('supabase_token', $loginResult['access_token']);
                Session::put('user', $loginResult['user']);
                Session::put('user_id', $loginResult['user']['id']);

                // Migrate pre-auth locale cookie to database
                $this->migratePreAuthLocale($request, $loginResult['user']['id']);

                // Ensure user has locale preference in database
                $this->ensureUserLocalePreference($request, $loginResult['user']['id'], $loginResult['access_token']);

                return redirect()->route('dashboard')
                    ->with('info', __('Registration completed. If you experience any issues, please contact support.'));
            }

            // If login also failed, user probably wasn't created
            return back()->withErrors(['email' => __('Registration encountered an error. Please try again or contact support if the problem persists.')]);
        }

        // Check if registration returned an access token (email confirmation disabled)
        if ($result && isset($result['access_token'])) {
            // Registration successful with immediate access
            Session::put('supabase_token', $result['access_token']);
            Session::put('user', $result['user']);
            Session::put('user_id', $result['user']['id']);

            // Migrate pre-auth locale cookie to database
            $this->migratePreAuthLocale($request, $result['user']['id']);

            // Ensure user has locale preference in database
            $this->ensureUserLocalePreference($request, $result['user']['id'], $result['access_token']);

            return redirect()->route('dashboard');
        }

        // Check if user was created but needs email confirmation
        if ($result && (isset($result['id']) || isset($result['user']))) {
            // Try to sign in immediately after registration
            $loginResult = $this->supabase->signIn($validated['email'], $validated['password']);

            \Log::info('Auto-login after registration', [
                'email' => $validated['email'],
                'loginResult' => $loginResult,
            ]);

            if ($loginResult && isset($loginResult['access_token'])) {
                Session::put('supabase_token', $loginResult['access_token']);
                Session::put('user', $loginResult['user']);
                Session::put('user_id', $loginResult['user']['id']);

                // Migrate pre-auth locale cookie to database
                $this->migratePreAuthLocale($request, $loginResult['user']['id']);

                // Ensure user has locale preference in database
                $this->ensureUserLocalePreference($request, $loginResult['user']['id'], $loginResult['access_token']);

                return redirect()->route('dashboard');
            }

            // If auto-login fails, but user was created, show success message
            if (isset($result['confirmation_sent_at'])) {
                return redirect()->route('login')
                    ->with('success', __('Registration successful! Please check your email to confirm your account.'));
            }

            // User created but couldn't auto-login for some reason
            return redirect()->route('login')
                ->with('success', __('Registration successful! You can now log in.'));
        }

        // Fallback error - provide more context
        \Log::error('Registration failed with no clear reason', [
            'email' => $validated['email'],
            'result' => $result,
        ]);

        return back()->withErrors(['email' => __('Registration could not be completed. Please ensure all fields are correct and try again.')]);
    }

    public function logout()
    {
        Session::forget('supabase_token');
        Session::forget('user');
        Auth::logout();

        return redirect()->route('login');
    }

    public function confirmEmail(Request $request)
    {
        // Handle the confirmation from Supabase
        // The URL will have format: /auth/confirm#access_token=...&token_type=...&type=signup
        // Since the token is in the fragment (after #), we need to handle it client-side

        return view('auth.confirm');
    }

    /**
     * Migrate pre-authentication locale cookie to user database preference
     */
    private function migratePreAuthLocale(Request $request, string $userId): void
    {
        try {
            // Check if user has pre-auth locale cookie
            if (! $request->hasCookie('pre_auth_locale')) {
                return;
            }

            $cookieLocale = $request->cookie('pre_auth_locale');
            if (empty($cookieLocale) || ! in_array($cookieLocale, ['en', 'ru'])) {
                return;
            }

            // Get access token from session
            $accessToken = Session::get('supabase_token');
            if (! $accessToken) {
                \Log::warning('Cannot migrate locale: no access token', [
                    'user_id' => $userId,
                    'cookie_locale' => $cookieLocale,
                ]);

                return;
            }

            // Set up Supabase client with user token
            $this->supabase->setUserToken($accessToken);

            // Try to save locale to user_preferences
            try {
                // Check if preference already exists
                $existing = $this->supabase->from('user_preferences')
                    ->select('id')
                    ->eq('user_id', $userId)
                    ->single();

                if ($existing) {
                    // Update existing preference
                    $this->supabase->from('user_preferences')
                        ->update([
                            'locale' => $cookieLocale,
                            'updated_at' => now()->toISOString(),
                        ])
                        ->eq('user_id', $userId);
                } else {
                    // Insert new preference
                    $this->supabase->from('user_preferences')
                        ->insert([
                            'user_id' => $userId,
                            'locale' => $cookieLocale,
                            'updated_at' => now()->toISOString(),
                        ]);
                }

                // Update session locale
                Session::put('locale', $cookieLocale);

                \Log::info('Successfully migrated pre-auth locale to database', [
                    'user_id' => $userId,
                    'locale' => $cookieLocale,
                    'action' => $existing ? 'updated' : 'created',
                ]);

                // Clear the pre-auth cookie by setting it to expire
                \Cookie::queue(\Cookie::forget('pre_auth_locale'));

            } catch (\Exception $dbError) {
                \Log::error('Failed to save migrated locale to database', [
                    'user_id' => $userId,
                    'locale' => $cookieLocale,
                    'error' => $dbError->getMessage(),
                ]);

                // At least update session
                Session::put('locale', $cookieLocale);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to migrate pre-auth locale', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ensure user has a locale preference in the database, create default if needed
     */
    private function ensureUserLocalePreference(Request $request, string $userId, string $accessToken): void
    {
        try {
            // Set up Supabase client with user token
            $this->supabase->setUserToken($accessToken);

            // Check if user already has locale preference
            $existing = $this->supabase->from('user_preferences')
                ->select('id, locale')
                ->eq('user_id', $userId)
                ->single();

            if ($existing) {
                // User already has preference, set session locale to match
                Session::put('locale', $existing['locale']);
                \Log::info('Found existing user locale preference', [
                    'user_id' => $userId,
                    'locale' => $existing['locale'],
                ]);

                return;
            }

            // No preference exists, create one with default locale
            $defaultLocale = session('locale', config('app.locale', 'en'));

            // Insert new preference with default locale
            $result = $this->supabase->from('user_preferences')
                ->insert([
                    'user_id' => $userId,
                    'locale' => $defaultLocale,
                    'updated_at' => now()->toISOString(),
                ]);

            // Update session locale
            Session::put('locale', $defaultLocale);

            \Log::info('Created default user locale preference', [
                'user_id' => $userId,
                'locale' => $defaultLocale,
                'insert_result' => $result,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to ensure user locale preference', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
