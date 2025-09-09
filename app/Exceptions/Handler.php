<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Always log exceptions with full context
            Log::error('Unhandled exception occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Log render attempts for debugging
        Log::debug('Exception render called', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'status_code' => $this->getStatusCode($e),
        ]);

        return parent::render($request, $e);
    }

    /**
     * Get HTTP status code for exception
     */
    private function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return 500;
    }
}
