<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FlashcardErrorService
{
    private const ERROR_CACHE_TTL = 3600; // 1 hour

    private const ERROR_RATE_LIMIT = 10; // Max errors per minute per operation

    /**
     * Error severity levels
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Error categories
     */
    public const CATEGORY_VALIDATION = 'validation';

    public const CATEGORY_DATABASE = 'database';

    public const CATEGORY_IMPORT = 'import';

    public const CATEGORY_EXPORT = 'export';

    public const CATEGORY_SEARCH = 'search';

    public const CATEGORY_PERFORMANCE = 'performance';

    public const CATEGORY_SYSTEM = 'system';

    /**
     * Handle and categorize flashcard-related errors
     *
     * @return array Error details and response
     */
    public function handleError(\Exception $exception, string $operation, array $context = []): array
    {
        $errorDetails = $this->analyzeError($exception, $operation, $context);

        // Log the error
        $this->logError($errorDetails);

        // Track error rate
        $this->trackErrorRate($operation);

        // Generate user-friendly response
        $response = $this->generateErrorResponse($errorDetails);

        // Check if circuit breaker should be triggered
        $this->checkCircuitBreaker($operation);

        return [
            'error_details' => $errorDetails,
            'response' => $response,
            'should_retry' => $this->shouldRetry($errorDetails),
            'recovery_suggestions' => $this->getRecoverySuggestions($errorDetails),
        ];
    }

    /**
     * Analyze error and categorize it
     */
    public function analyzeError(\Exception $exception, string $operation, array $context = []): array
    {
        $errorType = get_class($exception);
        $message = $exception->getMessage();
        $code = $exception->getCode();

        // Categorize the error
        $category = $this->categorizeError($exception, $operation);
        $severity = $this->determineSeverity($exception, $category);

        // Extract additional information
        $additionalInfo = $this->extractAdditionalInfo($exception, $context);

        return [
            'id' => uniqid('err_', true),
            'timestamp' => now()->toISOString(),
            'operation' => $operation,
            'category' => $category,
            'severity' => $severity,
            'exception_type' => $errorType,
            'message' => $message,
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->getRelevantStackTrace($exception),
            'context' => $context,
            'additional_info' => $additionalInfo,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Generate user-friendly error response
     */
    public function generateErrorResponse(array $errorDetails): array
    {
        $category = $errorDetails['category'];
        $severity = $errorDetails['severity'];
        $operation = $errorDetails['operation'];

        // Get appropriate user message
        $userMessage = $this->getUserFriendlyMessage($category, $operation, $severity);

        // Determine HTTP status code
        $statusCode = $this->getHttpStatusCode($category, $errorDetails['exception_type']);

        // Get recovery actions
        $recoveryActions = $this->getRecoveryActions($category, $operation);

        return [
            'success' => false,
            'error' => [
                'id' => $errorDetails['id'],
                'message' => $userMessage,
                'category' => $category,
                'severity' => $severity,
                'recoverable' => $this->isRecoverable($errorDetails),
                'recovery_actions' => $recoveryActions,
                'support_info' => $this->getSupportInfo($errorDetails),
            ],
            'status_code' => $statusCode,
            'timestamp' => $errorDetails['timestamp'],
        ];
    }

    /**
     * Get error statistics
     */
    public function getErrorStatistics(?string $operation = null, int $hours = 24): array
    {
        $cacheKey = 'flashcard_error_stats:'.($operation ?? 'all').':'.$hours;

        return Cache::remember($cacheKey, self::ERROR_CACHE_TTL, function () use ($operation, $hours) {
            return $this->calculateErrorStatistics($operation, $hours);
        });
    }

    /**
     * Get error trends
     */
    public function getErrorTrends(int $days = 7): array
    {
        // This would typically query an error log table
        return [
            'period_days' => $days,
            'total_errors' => 145,
            'daily_breakdown' => [
                ['date' => now()->subDays(6)->toDateString(), 'count' => 18],
                ['date' => now()->subDays(5)->toDateString(), 'count' => 22],
                ['date' => now()->subDays(4)->toDateString(), 'count' => 15],
                ['date' => now()->subDays(3)->toDateString(), 'count' => 31],
                ['date' => now()->subDays(2)->toDateString(), 'count' => 24],
                ['date' => now()->subDays(1)->toDateString(), 'count' => 19],
                ['date' => now()->toDateString(), 'count' => 16],
            ],
            'category_breakdown' => [
                self::CATEGORY_VALIDATION => 45,
                self::CATEGORY_DATABASE => 23,
                self::CATEGORY_IMPORT => 34,
                self::CATEGORY_SEARCH => 18,
                self::CATEGORY_EXPORT => 12,
                self::CATEGORY_PERFORMANCE => 8,
                self::CATEGORY_SYSTEM => 5,
            ],
            'severity_breakdown' => [
                self::SEVERITY_LOW => 89,
                self::SEVERITY_MEDIUM => 38,
                self::SEVERITY_HIGH => 15,
                self::SEVERITY_CRITICAL => 3,
            ],
        ];
    }

    /**
     * Check if operation should be retried
     */
    public function shouldRetry(array $errorDetails): bool
    {
        $category = $errorDetails['category'];
        $severity = $errorDetails['severity'];
        $exceptionType = $errorDetails['exception_type'];

        // Never retry critical errors
        if ($severity === self::SEVERITY_CRITICAL) {
            return false;
        }

        // Don't retry validation errors
        if ($category === self::CATEGORY_VALIDATION) {
            return false;
        }

        // Retry database connection errors
        if ($category === self::CATEGORY_DATABASE &&
            str_contains($errorDetails['message'], 'connection')) {
            return true;
        }

        // Retry timeout errors
        if (str_contains($errorDetails['message'], 'timeout')) {
            return true;
        }

        // Retry temporary system errors
        if ($category === self::CATEGORY_SYSTEM && $severity === self::SEVERITY_LOW) {
            return true;
        }

        return false;
    }

    /**
     * Get recovery suggestions for users
     */
    public function getRecoverySuggestions(array $errorDetails): array
    {
        $category = $errorDetails['category'];
        $operation = $errorDetails['operation'];

        $suggestions = [];

        switch ($category) {
            case self::CATEGORY_VALIDATION:
                $suggestions = [
                    'Check that all required fields are filled out correctly',
                    'Verify that file formats match the expected types',
                    'Ensure data meets the specified requirements',
                ];
                break;

            case self::CATEGORY_DATABASE:
                $suggestions = [
                    'Try refreshing the page and attempting the operation again',
                    'Check your internet connection',
                    'If the problem persists, please contact support',
                ];
                break;

            case self::CATEGORY_IMPORT:
                $suggestions = [
                    'Verify that your import file is in the correct format',
                    'Check that the file is not corrupted or too large',
                    'Try importing a smaller batch of data',
                    'Review the import format documentation',
                ];
                break;

            case self::CATEGORY_EXPORT:
                $suggestions = [
                    'Try exporting fewer items at once',
                    'Check available storage space',
                    'Ensure you have permission to download files',
                ];
                break;

            case self::CATEGORY_SEARCH:
                $suggestions = [
                    'Try simplifying your search query',
                    'Check for typos in your search terms',
                    'Use fewer filters or broader criteria',
                ];
                break;

            case self::CATEGORY_PERFORMANCE:
                $suggestions = [
                    'Try again in a few moments',
                    'Consider reducing the amount of data processed',
                    'Check your internet connection speed',
                ];
                break;

            default:
                $suggestions = [
                    'Try refreshing the page',
                    'Clear your browser cache and cookies',
                    'Contact support if the issue persists',
                ];
        }

        return $suggestions;
    }

    /**
     * Categorize error based on exception and operation
     */
    private function categorizeError(\Exception $exception, string $operation): string
    {
        $message = strtolower($exception->getMessage());
        $exceptionType = get_class($exception);

        // Database-related errors
        if (str_contains($exceptionType, 'Database') ||
            str_contains($exceptionType, 'QueryException') ||
            str_contains($message, 'database') ||
            str_contains($message, 'connection')) {
            return self::CATEGORY_DATABASE;
        }

        // Validation errors
        if (str_contains($exceptionType, 'Validation') ||
            str_contains($message, 'validation')) {
            return self::CATEGORY_VALIDATION;
        }

        // Operation-based categorization
        if (str_contains($operation, 'import')) {
            return self::CATEGORY_IMPORT;
        }

        if (str_contains($operation, 'export')) {
            return self::CATEGORY_EXPORT;
        }

        if (str_contains($operation, 'search')) {
            return self::CATEGORY_SEARCH;
        }

        // Performance-related
        if (str_contains($message, 'timeout') ||
            str_contains($message, 'memory') ||
            str_contains($message, 'time limit')) {
            return self::CATEGORY_PERFORMANCE;
        }

        return self::CATEGORY_SYSTEM;
    }

    /**
     * Determine error severity
     */
    private function determineSeverity(\Exception $exception, string $category): string
    {
        $message = strtolower($exception->getMessage());

        // Critical errors
        if (str_contains($message, 'fatal') ||
            str_contains($message, 'segmentation fault') ||
            str_contains($message, 'out of memory')) {
            return self::SEVERITY_CRITICAL;
        }

        // High severity errors
        if ($category === self::CATEGORY_DATABASE ||
            str_contains($message, 'connection refused') ||
            str_contains($message, 'access denied')) {
            return self::SEVERITY_HIGH;
        }

        // Medium severity errors
        if ($category === self::CATEGORY_IMPORT ||
            $category === self::CATEGORY_EXPORT ||
            str_contains($message, 'timeout')) {
            return self::SEVERITY_MEDIUM;
        }

        // Low severity (validation, minor issues)
        return self::SEVERITY_LOW;
    }

    /**
     * Extract additional information from context
     */
    private function extractAdditionalInfo(\Exception $exception, array $context): array
    {
        $info = [];

        // Memory usage at time of error
        $info['memory_usage_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
        $info['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        // Request information
        if (request()) {
            $info['request_method'] = request()->method();
            $info['request_url'] = request()->fullUrl();
            $info['request_size_kb'] = round(strlen(json_encode(request()->all())) / 1024, 2);
        }

        // Database connection info
        try {
            $info['db_connection'] = config('database.default');
        } catch (\Exception $e) {
            $info['db_connection'] = 'unknown';
        }

        return $info;
    }

    /**
     * Get relevant stack trace (limit to application files)
     */
    private function getRelevantStackTrace(\Exception $exception): array
    {
        $trace = $exception->getTrace();
        $appPath = base_path('app');

        return array_filter($trace, function ($frame) use ($appPath) {
            return isset($frame['file']) && str_starts_with($frame['file'], $appPath);
        });
    }

    /**
     * Get user-friendly message
     */
    private function getUserFriendlyMessage(string $category, string $operation, string $severity): string
    {
        $messages = [
            self::CATEGORY_VALIDATION => [
                self::SEVERITY_LOW => 'Please check your input and try again.',
                self::SEVERITY_MEDIUM => 'There was an issue with the provided data.',
                self::SEVERITY_HIGH => 'The data provided does not meet the required format.',
            ],
            self::CATEGORY_DATABASE => [
                self::SEVERITY_LOW => 'A temporary database issue occurred. Please try again.',
                self::SEVERITY_MEDIUM => 'We\'re experiencing database connectivity issues.',
                self::SEVERITY_HIGH => 'Database service is temporarily unavailable.',
                self::SEVERITY_CRITICAL => 'Critical database error. Please contact support immediately.',
            ],
            self::CATEGORY_IMPORT => [
                self::SEVERITY_LOW => 'There was an issue importing your file. Please check the format.',
                self::SEVERITY_MEDIUM => 'Import failed due to file format or size issues.',
                self::SEVERITY_HIGH => 'Import operation could not be completed.',
            ],
            self::CATEGORY_EXPORT => [
                self::SEVERITY_LOW => 'Export encountered a minor issue. Please try again.',
                self::SEVERITY_MEDIUM => 'Export operation failed. Please try with fewer items.',
                self::SEVERITY_HIGH => 'Export service is currently unavailable.',
            ],
            self::CATEGORY_SEARCH => [
                self::SEVERITY_LOW => 'Search query could not be processed. Please try different terms.',
                self::SEVERITY_MEDIUM => 'Search operation timed out. Please try a simpler query.',
                self::SEVERITY_HIGH => 'Search service is temporarily unavailable.',
            ],
            self::CATEGORY_PERFORMANCE => [
                self::SEVERITY_LOW => 'Operation is taking longer than expected. Please wait.',
                self::SEVERITY_MEDIUM => 'System is experiencing high load. Please try again shortly.',
                self::SEVERITY_HIGH => 'Performance issue detected. Operation was cancelled.',
            ],
        ];

        return $messages[$category][$severity] ?? 'An unexpected error occurred. Please try again or contact support.';
    }

    /**
     * Calculate error statistics
     */
    private function calculateErrorStatistics(?string $operation, int $hours): array
    {
        // This would typically query an error log table
        // Mock implementation with realistic data
        return [
            'time_period_hours' => $hours,
            'operation' => $operation,
            'total_errors' => 34,
            'error_rate' => 0.012, // 1.2% error rate
            'by_category' => [
                self::CATEGORY_VALIDATION => 15,
                self::CATEGORY_DATABASE => 8,
                self::CATEGORY_IMPORT => 6,
                self::CATEGORY_SEARCH => 3,
                self::CATEGORY_EXPORT => 2,
            ],
            'by_severity' => [
                self::SEVERITY_LOW => 22,
                self::SEVERITY_MEDIUM => 9,
                self::SEVERITY_HIGH => 3,
                self::SEVERITY_CRITICAL => 0,
            ],
            'resolution_rate' => 0.89, // 89% of errors were resolved
            'average_resolution_time_minutes' => 12.5,
        ];
    }

    /**
     * Log error with appropriate level
     */
    private function logError(array $errorDetails): void
    {
        $severity = $errorDetails['severity'];

        $logLevel = match ($severity) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_HIGH => 'error',
            self::SEVERITY_MEDIUM => 'warning',
            default => 'info'
        };

        Log::{$logLevel}('Flashcard operation error', $errorDetails);
    }

    /**
     * Track error rate for circuit breaker
     */
    private function trackErrorRate(string $operation): void
    {
        $key = "error_rate:{$operation}:".now()->format('Y-m-d-H-i');
        $currentCount = Cache::get($key, 0);
        Cache::put($key, $currentCount + 1, 300); // 5 minutes TTL
    }

    /**
     * Check if circuit breaker should be triggered
     */
    private function checkCircuitBreaker(string $operation): bool
    {
        $key = "error_rate:{$operation}:".now()->format('Y-m-d-H-i');
        $errorCount = Cache::get($key, 0);

        if ($errorCount >= self::ERROR_RATE_LIMIT) {
            // Trigger circuit breaker
            Cache::put("circuit_breaker:{$operation}", true, 600); // 10 minutes
            Log::warning("Circuit breaker triggered for operation: {$operation}");

            return true;
        }

        return false;
    }

    /**
     * Get HTTP status code for error
     */
    private function getHttpStatusCode(string $category, string $exceptionType): int
    {
        if ($category === self::CATEGORY_VALIDATION) {
            return 422;
        }

        if (str_contains($exceptionType, 'NotFound')) {
            return 404;
        }

        if (str_contains($exceptionType, 'Unauthorized')) {
            return 401;
        }

        if (str_contains($exceptionType, 'Forbidden')) {
            return 403;
        }

        return 500;
    }

    /**
     * Get recovery actions for user
     */
    private function getRecoveryActions(string $category, string $operation): array
    {
        return [
            [
                'label' => 'Try Again',
                'action' => 'retry',
                'primary' => true,
            ],
            [
                'label' => 'Go Back',
                'action' => 'back',
                'primary' => false,
            ],
            [
                'label' => 'Contact Support',
                'action' => 'support',
                'primary' => false,
            ],
        ];
    }

    /**
     * Check if error is recoverable
     */
    private function isRecoverable(array $errorDetails): bool
    {
        return $errorDetails['severity'] !== self::SEVERITY_CRITICAL &&
               $errorDetails['category'] !== self::CATEGORY_VALIDATION;
    }

    /**
     * Get support information
     */
    private function getSupportInfo(array $errorDetails): array
    {
        return [
            'error_id' => $errorDetails['id'],
            'timestamp' => $errorDetails['timestamp'],
            'contact_email' => config('app.support_email', 'support@example.com'),
            'include_in_report' => [
                'error_id',
                'timestamp',
                'operation',
                'steps_to_reproduce',
            ],
        ];
    }
}
