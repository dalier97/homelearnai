<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/locale/session',  // Guest users language switching
        '/locale/user',     // Authenticated users language switching
        '/api/*',           // All API routes should return proper authentication status codes
        'api/*',            // All API routes without leading slash
        '*/flashcards/*',   // All flashcard routes
        'api/flashcards/*', // Specific flashcard API routes
        '/api/flashcards/*', // Specific flashcard API routes with leading slash
    ];

    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        // Completely bypass CSRF in testing environment
        if (app()->environment('testing')) {
            // Log that we're bypassing CSRF
            \Log::info('VerifyCsrfToken: Bypassing CSRF in testing environment', [
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'environment' => app()->environment(),
            ]);

            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     */
    protected function tokensMatch($request): bool
    {
        // In testing environment, bypass CSRF for JSON requests to API routes
        if (app()->environment('testing') &&
            $request->expectsJson() &&
            str_starts_with($request->getPathInfo(), '/api/')) {
            return true;
        }

        // Also bypass CSRF for all API routes during testing (more permissive)
        if (app()->environment('testing') && str_starts_with($request->getPathInfo(), '/api/')) {
            return true;
        }

        return parent::tokensMatch($request);
    }
}
