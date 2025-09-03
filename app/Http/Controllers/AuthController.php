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

            return redirect()->route('dashboard');
        }

        // Check for specific error codes
        if ($result && isset($result['error'])) {
            $errorMessage = $result['error'];

            // Add more helpful messages based on error
            if (isset($result['details']['error_code'])) {
                switch ($result['details']['error_code']) {
                    case 'invalid_credentials':
                        $errorMessage = 'Invalid email or password. If you just registered, please check your email to confirm your account first.';
                        break;
                    case 'email_not_confirmed':
                        $errorMessage = 'Please confirm your email address before logging in. Check your inbox for the confirmation email.';
                        break;
                    default:
                        $errorMessage = $result['error'];
                }
            }

            return back()->withErrors(['email' => $errorMessage]);
        }

        return back()->withErrors(['email' => 'Unable to log in. Please try again.']);
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

        // Check for errors first
        if ($result && isset($result['error'])) {
            return back()->withErrors(['email' => $result['error']]);
        }

        // Check if registration returned an access token (email confirmation disabled)
        if ($result && isset($result['access_token'])) {
            // Registration successful with immediate access
            Session::put('supabase_token', $result['access_token']);
            Session::put('user', $result['user']);
            Session::put('user_id', $result['user']['id']);

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

                return redirect()->route('dashboard');
            }

            // If auto-login fails, but user was created, show success message
            if (isset($result['confirmation_sent_at'])) {
                return redirect()->route('login')
                    ->with('success', 'Registration successful! Please check your email to confirm your account.');
            }

            // User created but couldn't auto-login for some reason
            return redirect()->route('login')
                ->with('success', 'Registration successful! You can now log in.');
        }

        // Fallback error
        return back()->withErrors(['email' => 'Registration failed. Please try again.']);
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
}
