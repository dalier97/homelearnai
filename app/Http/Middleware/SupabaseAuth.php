<?php

namespace App\Http\Middleware;

use App\Services\SupabaseClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupabaseAuth
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $hasToken = Session::has('supabase_token');
        $hasUserId = Session::has('user_id');
        $accessToken = Session::get('supabase_token');
        $userId = Session::get('user_id');

        // Debug logging for authentication state
        \Log::debug('SupabaseAuth middleware check', [
            'url' => $request->url(),
            'method' => $request->method(),
            'has_token' => $hasToken,
            'has_user_id' => $hasUserId,
            'user_id' => $userId,
            'token_length' => $accessToken ? strlen($accessToken) : 0,
            'session_id' => Session::getId(),
            'is_htmx' => $request->header('HX-Request') === 'true',
        ]);

        if (! $hasToken || ! $hasUserId) {
            \Log::warning('SupabaseAuth middleware: Missing authentication data', [
                'url' => $request->url(),
                'has_token' => $hasToken,
                'has_user_id' => $hasUserId,
                'session_id' => Session::getId(),
                'environment' => app()->environment(),
            ]);

            // In testing environment, log additional debug info but still redirect
            if (app()->environment('testing')) {
                \Log::debug('Testing environment session debug info', [
                    'all_session_data' => Session::all(),
                    'session_driver' => config('session.driver'),
                    'session_lifetime' => config('session.lifetime'),
                    'request_cookies' => $request->cookies->all(),
                    'session_started' => Session::isStarted(),
                ]);
            }

            return redirect()->route('login');
        }

        // Configure SupabaseClient with user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
            \Log::debug('SupabaseAuth middleware: Token configured for RLS', [
                'user_id' => $userId,
                'token_length' => strlen($accessToken),
                'url' => $request->url(),
            ]);
        } else {
            \Log::error('SupabaseAuth middleware: Token exists in session but is empty', [
                'user_id' => $userId,
                'session_id' => Session::getId(),
                'url' => $request->url(),
            ]);
        }

        return $next($request);
    }
}
