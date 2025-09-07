<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class KidsModeSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Apply additional security headers when in kids mode to prevent
     * unauthorized access and protect the child's browsing session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply enhanced security headers when kids mode is active
        if (Session::get('kids_mode_active')) {
            $this->applyKidsModeSecurityHeaders($response, $request);
        }

        return $response;
    }

    /**
     * Apply comprehensive security headers for kids mode
     */
    private function applyKidsModeSecurityHeaders(Response $response, Request $request): void
    {
        // Prevent the page from being embedded in frames (clickjacking protection)
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent XSS attacks
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Force HTTPS (if not already HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Disable referrer information leakage
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP) - restrictive for kids mode
        $csp = $this->buildKidsModeCsp($request);
        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy (formerly Feature Policy)
        $permissionsPolicy = $this->buildPermissionsPolicy();
        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // Disable caching for sensitive pages
        if ($this->isSensitiveKidsModePage($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }

    /**
     * Build Content Security Policy for kids mode
     */
    private function buildKidsModeCsp(Request $request): string
    {
        $host = $request->getSchemeAndHttpHost();

        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Needed for HTMX and Alpine.js
            "style-src 'self' 'unsafe-inline'", // Needed for Tailwind CSS
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'", // Disable plugins
            "embed-src 'none'",   // Disable embeds
            "child-src 'none'",   // Disable frames/workers
            "frame-src 'none'",   // Disable all frames
            "worker-src 'none'",  // Disable web workers
            "manifest-src 'self'",
            "form-action 'self'", // Only allow forms to submit to same origin
            "frame-ancestors 'none'", // Prevent embedding (redundant with X-Frame-Options)
            "base-uri 'self'",    // Restrict base URI
        ];

        return implode('; ', $policies);
    }

    /**
     * Build Permissions Policy to restrict browser features
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'camera=()',           // Disable camera access
            'microphone=()',       // Disable microphone access
            'geolocation=()',      // Disable location access
            'accelerometer=()',    // Disable motion sensors
            'gyroscope=()',        // Disable gyroscope
            'magnetometer=()',     // Disable magnetometer
            'usb=()',             // Disable USB device access
            'midi=()',            // Disable MIDI device access
            'encrypted-media=()', // Disable encrypted media
            'payment=()',         // Disable payment API
            'web-share=()',       // Disable web share API
            'fullscreen=(self)',  // Allow fullscreen only for same origin
            'display-capture=()', // Disable screen capture
        ];

        return implode(', ', $policies);
    }

    /**
     * Check if current page is sensitive and should not be cached
     */
    private function isSensitiveKidsModePage(Request $request): bool
    {
        $sensitiveRoutes = [
            'kids-mode.exit',
            'kids-mode.exit.validate',
            'kids-mode.settings',
            'kids-mode.pin.update',
            'kids-mode.pin.reset',
        ];

        $routeName = $request->route()?->getName();

        return $routeName && in_array($routeName, $sensitiveRoutes);
    }
}
