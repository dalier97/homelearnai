<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlashcardPerformanceService
{
    private const METRICS_CACHE_TTL = 300; // 5 minutes

    private const PERFORMANCE_LOG_PREFIX = 'flashcard_performance:';

    /**
     * Start performance monitoring for an operation
     *
     * @return string Monitoring ID
     */
    public function startMonitoring(string $operation, array $context = []): string
    {
        $monitoringId = uniqid($operation.'_', true);

        $startData = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_peak_memory' => memory_get_peak_usage(true),
            'context' => $context,
            'db_query_count' => $this->getDbQueryCount(),
        ];

        Cache::put(
            self::PERFORMANCE_LOG_PREFIX.$monitoringId,
            $startData,
            now()->addMinutes(10)
        );

        return $monitoringId;
    }

    /**
     * End performance monitoring and log results
     *
     * @return array Performance metrics
     */
    public function endMonitoring(string $monitoringId, array $additionalData = []): array
    {
        $startData = Cache::get(self::PERFORMANCE_LOG_PREFIX.$monitoringId);

        if (! $startData) {
            Log::warning("Performance monitoring data not found for ID: {$monitoringId}");

            return [];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);
        $endDbQueryCount = $this->getDbQueryCount();

        $metrics = [
            'operation' => $startData['operation'],
            'monitoring_id' => $monitoringId,
            'duration_ms' => round(($endTime - $startData['start_time']) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startData['start_memory']) / 1024 / 1024, 2),
            'peak_memory_mb' => round($endPeakMemory / 1024 / 1024, 2),
            'db_queries' => $endDbQueryCount - $startData['db_query_count'],
            'context' => $startData['context'],
            'additional_data' => $additionalData,
            'timestamp' => now()->toISOString(),
        ];

        // Log performance metrics
        $this->logPerformanceMetrics($metrics);

        // Clean up monitoring data
        Cache::forget(self::PERFORMANCE_LOG_PREFIX.$monitoringId);

        return $metrics;
    }

    /**
     * Monitor a closure execution
     *
     * @return mixed Callback result with performance data
     */
    public function monitor(string $operation, callable $callback, array $context = [])
    {
        $monitoringId = $this->startMonitoring($operation, $context);

        try {
            $result = $callback();

            $metrics = $this->endMonitoring($monitoringId, [
                'success' => true,
                'result_type' => gettype($result),
                'result_size' => $this->getResultSize($result),
            ]);

            return [
                'result' => $result,
                'performance' => $metrics,
            ];

        } catch (\Exception $e) {
            $this->endMonitoring($monitoringId, [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Get performance metrics for flashcard operations
     */
    public function getPerformanceMetrics(?int $unitId = null, int $hours = 24): array
    {
        $cacheKey = 'flashcard_performance_metrics:'.($unitId ?? 'all').':'.$hours;

        return Cache::remember($cacheKey, self::METRICS_CACHE_TTL, function () use ($unitId, $hours) {
            return $this->calculatePerformanceMetrics($unitId, $hours);
        });
    }

    /**
     * Get slow operations report
     */
    public function getSlowOperations(float $thresholdMs = 1000, int $limit = 20): array
    {
        // This would typically query a performance log table
        // For now, we'll return a mock implementation

        return [
            'threshold_ms' => $thresholdMs,
            'operations' => [
                [
                    'operation' => 'flashcard_search',
                    'average_duration_ms' => 1250.5,
                    'max_duration_ms' => 2100.3,
                    'occurrences' => 45,
                    'last_occurrence' => now()->subMinutes(15)->toISOString(),
                ],
                [
                    'operation' => 'flashcard_import',
                    'average_duration_ms' => 3400.2,
                    'max_duration_ms' => 8500.1,
                    'occurrences' => 8,
                    'last_occurrence' => now()->subHours(2)->toISOString(),
                ],
            ],
        ];
    }

    /**
     * Get memory usage analysis
     */
    public function getMemoryAnalysis(): array
    {
        return [
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
            'recommendations' => $this->getMemoryRecommendations(),
        ];
    }

    /**
     * Get database performance metrics
     */
    public function getDatabaseMetrics(): array
    {
        $metrics = [
            'connection_info' => [
                'driver' => config('database.default'),
                'host' => config('database.connections.'.config('database.default').'.host'),
                'database' => config('database.connections.'.config('database.default').'.database'),
            ],
            'query_count' => $this->getDbQueryCount(),
            'slow_query_threshold' => 1.0, // seconds
        ];

        // Add PostgreSQL specific metrics if applicable
        if (config('database.default') === 'pgsql') {
            $metrics['postgresql'] = $this->getPostgreSQLMetrics();
        }

        return $metrics;
    }

    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics(): array
    {
        $cacheDriver = config('cache.default');

        $metrics = [
            'driver' => $cacheDriver,
            'hit_rate' => $this->calculateCacheHitRate(),
            'keys_count' => $this->getCacheKeysCount(),
            'memory_usage' => $this->getCacheMemoryUsage(),
        ];

        // Add Redis specific metrics if applicable
        if ($cacheDriver === 'redis') {
            $metrics['redis'] = $this->getRedisMetrics();
        }

        return $metrics;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(?int $unitId = null): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'unit_id' => $unitId,
            'performance_metrics' => $this->getPerformanceMetrics($unitId),
            'slow_operations' => $this->getSlowOperations(),
            'memory_analysis' => $this->getMemoryAnalysis(),
            'database_metrics' => $this->getDatabaseMetrics(),
            'cache_metrics' => $this->getCacheMetrics(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(array $metrics): void
    {
        $logLevel = $this->determineLogLevel($metrics);

        Log::{$logLevel}('Flashcard operation performance', $metrics);

        // Store metrics for aggregation (in a real implementation, this would go to a dedicated table/service)
        $this->storeMetricsForAggregation($metrics);
    }

    /**
     * Calculate performance metrics
     */
    private function calculatePerformanceMetrics(?int $unitId, int $hours): array
    {
        // This would typically query a performance metrics table
        // For now, we'll return a mock implementation with realistic data

        return [
            'time_period_hours' => $hours,
            'unit_id' => $unitId,
            'operations' => [
                'flashcard_list' => [
                    'count' => 156,
                    'avg_duration_ms' => 45.2,
                    'max_duration_ms' => 120.5,
                    'min_duration_ms' => 12.1,
                    'avg_memory_mb' => 2.4,
                    'avg_db_queries' => 3.2,
                ],
                'flashcard_search' => [
                    'count' => 89,
                    'avg_duration_ms' => 78.9,
                    'max_duration_ms' => 250.3,
                    'min_duration_ms' => 25.6,
                    'avg_memory_mb' => 1.8,
                    'avg_db_queries' => 2.1,
                ],
                'flashcard_import' => [
                    'count' => 12,
                    'avg_duration_ms' => 2340.7,
                    'max_duration_ms' => 8900.2,
                    'min_duration_ms' => 450.1,
                    'avg_memory_mb' => 15.6,
                    'avg_db_queries' => 45.3,
                ],
            ],
            'aggregated_stats' => [
                'total_operations' => 257,
                'overall_avg_duration_ms' => 125.4,
                'cache_hit_rate' => 0.78,
                'error_rate' => 0.023,
            ],
        ];
    }

    /**
     * Get current database query count
     */
    private function getDbQueryCount(): int
    {
        // This is a simplified implementation
        // In practice, you might use Laravel Debugbar or a custom query counter
        return count(DB::getQueryLog());
    }

    /**
     * Determine appropriate log level based on metrics
     */
    private function determineLogLevel(array $metrics): string
    {
        $duration = $metrics['duration_ms'];
        $memoryUsage = $metrics['memory_usage_mb'];
        $dbQueries = $metrics['db_queries'];

        // Critical performance issues
        if ($duration > 5000 || $memoryUsage > 50 || $dbQueries > 100) {
            return 'error';
        }

        // Warning thresholds
        if ($duration > 2000 || $memoryUsage > 20 || $dbQueries > 50) {
            return 'warning';
        }

        // Info for normal operations
        if ($duration > 1000) {
            return 'info';
        }

        // Debug for fast operations
        return 'debug';
    }

    /**
     * Store metrics for aggregation
     */
    private function storeMetricsForAggregation(array $metrics): void
    {
        // In a real implementation, this would store to a database table
        // or send to a metrics collection service like Prometheus

        $key = 'metrics_aggregation:'.date('Y-m-d-H');
        $existingMetrics = Cache::get($key, []);
        $existingMetrics[] = $metrics;

        Cache::put($key, $existingMetrics, now()->addHours(25));
    }

    /**
     * Get result size for monitoring
     *
     * @param  mixed  $result
     */
    private function getResultSize($result): int
    {
        if (is_string($result)) {
            return strlen($result);
        }

        if (is_array($result) || $result instanceof Collection) {
            return count($result);
        }

        if (is_object($result)) {
            return strlen(serialize($result));
        }

        return 0;
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateCacheHitRate(): float
    {
        // This would typically come from cache statistics
        return 0.78; // 78% hit rate (mock data)
    }

    /**
     * Get cache keys count
     */
    private function getCacheKeysCount(): int
    {
        // This would depend on the cache driver
        return 1247; // Mock data
    }

    /**
     * Get cache memory usage
     */
    private function getCacheMemoryUsage(): array
    {
        return [
            'used_mb' => 45.2,
            'max_mb' => 128.0,
            'usage_percentage' => 35.3,
        ];
    }

    /**
     * Get PostgreSQL specific metrics
     */
    private function getPostgreSQLMetrics(): array
    {
        try {
            $result = DB::select('
                SELECT 
                    pg_database_size(current_database()) as db_size,
                    pg_stat_get_db_numbackends(pg_database.oid) as connections
                FROM pg_database 
                WHERE datname = current_database()
            ');

            return [
                'database_size_mb' => round($result[0]->db_size / 1024 / 1024, 2),
                'active_connections' => $result[0]->connections,
                'version' => DB::select('SELECT version()')[0]->version,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get PostgreSQL metrics: '.$e->getMessage());

            return ['error' => 'Unable to retrieve PostgreSQL metrics'];
        }
    }

    /**
     * Get Redis specific metrics
     */
    private function getRedisMetrics(): array
    {
        // This would connect to Redis and get actual metrics
        return [
            'connected_clients' => 12,
            'used_memory_mb' => 23.4,
            'keyspace_hits' => 1547,
            'keyspace_misses' => 423,
        ];
    }

    /**
     * Get memory recommendations
     */
    private function getMemoryRecommendations(): array
    {
        $currentUsage = memory_get_usage(true) / 1024 / 1024;
        $peakUsage = memory_get_peak_usage(true) / 1024 / 1024;

        $recommendations = [];

        if ($peakUsage > 100) {
            $recommendations[] = 'Consider increasing memory_limit or optimizing memory usage';
        }

        if ($currentUsage > 50) {
            $recommendations[] = 'Monitor for memory leaks in long-running operations';
        }

        return $recommendations;
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(): array
    {
        return [
            'caching' => [
                'Consider enabling Redis for better cache performance',
                'Increase cache TTL for frequently accessed data',
                'Implement cache warming for critical operations',
            ],
            'database' => [
                'Add indexes for frequently searched columns',
                'Consider query optimization for slow operations',
                'Monitor connection pool size',
            ],
            'application' => [
                'Enable OPcache for better PHP performance',
                'Consider lazy loading for large datasets',
                'Implement pagination for large result sets',
            ],
        ];
    }
}
