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
    ];

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

        return parent::tokensMatch($request);
    }
}
