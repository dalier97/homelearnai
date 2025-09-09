<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request and log response details.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        // Log incoming request
        Log::info('Incoming request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'is_htmx' => $request->header('HX-Request') === 'true',
        ]);

        try {
            $response = $next($request);

            $duration = round((microtime(true) - $start) * 1000, 2);

            // Log response details
            $logLevel = $response->getStatusCode() >= 400 ? 'error' : 'info';

            Log::$logLevel('Request completed', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'user_id' => auth()->id(),
                'content_type' => $response->headers->get('Content-Type'),
            ]);

            // Log 500 errors with extra detail
            if ($response->getStatusCode() === 500) {
                Log::critical('500 Internal Server Error detected', [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'session_id' => session()->getId(),
                    'headers' => $request->headers->all(),
                    'content_preview' => substr($response->getContent(), 0, 1000),
                ]);
            }

            return $response;

        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::critical('Exception during request processing', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => $duration,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }
}
