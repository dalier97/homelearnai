<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Replace default CSRF middleware with our custom one that excludes locale routes
        $middleware->replace(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\VerifyCsrfToken::class
        );

        // Register middleware aliases for route-specific usage
        $middleware->alias([
            'kids-mode' => \App\Http\Middleware\KidsMode::class,
            'not-in-kids-mode' => \App\Http\Middleware\NotInKidsMode::class,
            'kids-mode-security' => \App\Http\Middleware\KidsModeSecurityHeaders::class,
        ]);

        // Register SetLocale middleware globally
        // It must run AFTER session middleware to access session data
        $middleware->web(
            append: [
                \App\Http\Middleware\SetLocale::class,
                // Apply KidsMode middleware globally to all web routes
                // It will only restrict when kids_mode_active is true in session
                \App\Http\Middleware\KidsMode::class,
                // Apply security headers for kids mode
                \App\Http\Middleware\KidsModeSecurityHeaders::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
