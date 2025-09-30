<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Metrics\PrometheusRegistry;
use App\Support\Observability\Metrics\StatsdClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class ObservabilityManager
{
    private readonly array $config;

    public function __construct(
        private readonly StatsdClient $metrics,
        private readonly LoggerInterface $logger,
        private readonly ?PrometheusRegistry $prometheus,
        array $config = []
    ) {
        $this->config = $config;
    }

    public function recordHttpRequest(
        string $method,
        string $routeIdentifier,
        int $statusCode,
        float $durationMs,
        array $context = []
    ): void {
        if (! $this->metricsEnabled()) {
            return;
        }

        $normalizedRoute = $this->normalizeIdentifier($routeIdentifier);
        $tags = array_filter([
            'method' => Str::lower($method),
            'route' => $normalizedRoute,
            'status_code' => (string) $statusCode,
        ]);

        $this->metrics->timing('http.server.duration', $durationMs, $tags);
        $this->metrics->increment('http.server.request', $tags);

        if ($statusCode >= 500) {
            $this->metrics->increment('http.server.errors', $tags);
        }

        $statusClass = sprintf('%dxx', (int) floor($statusCode / 100));

        $this->recordHttpPrometheusMetrics(
            $durationMs,
            $normalizedRoute,
            Str::lower($method),
            $statusClass,
            $statusCode >= 500
        );

        $threshold = Arr::get($this->config, 'http.slow_request_threshold_ms', 0.0);
        if ($threshold > 0 && $durationMs >= $threshold) {
            $this->logger->warning(
                'Slow HTTP request detected',
                array_merge($context, [
                    'route' => $routeIdentifier,
                    'method' => $method,
                    'status_code' => $statusCode,
                    'duration_ms' => round($durationMs, 2),
                ])
            );
        }
    }

    public function recordQueueJob(
        string $jobName,
        string $connection,
        ?string $queue,
        float $durationSeconds
    ): void {
        if (! $this->metricsEnabled()) {
            return;
        }

        $durationMs = $durationSeconds * 1000.0;
        $tags = array_filter([
            'job' => $this->normalizeIdentifier($jobName),
            'connection' => $this->normalizeIdentifier($connection),
            'queue' => $queue ? $this->normalizeIdentifier($queue) : null,
        ]);

        $this->metrics->timing('queue.job.duration', $durationMs, $tags);
        $this->metrics->increment('queue.job.processed', $tags);

        $this->prometheus?->observeHistogram(
            'queue_job_duration_seconds',
            $durationMs / 1000.0,
            $tags
        );
        $this->prometheus?->observeHistogram(
            'queue_job_duration_seconds',
            $durationMs / 1000.0,
            [
                'job' => '__all__',
                'connection' => $tags['connection'] ?? 'default',
                'queue' => $tags['queue'] ?? 'default',
            ]
        );
        $this->prometheus?->incrementCounter(
            'queue_job_processed_total',
            $tags
        );
        $this->prometheus?->incrementCounter(
            'queue_job_processed_total',
            [
                'job' => '__all__',
                'connection' => $tags['connection'] ?? 'default',
                'queue' => $tags['queue'] ?? 'default',
            ]
        );

        $threshold = Arr::get($this->config, 'queue.slow_job_threshold_ms', 0.0);
        if ($threshold > 0 && $durationMs >= $threshold) {
            $this->logger->notice('Slow queue job detected', [
                'job' => $jobName,
                'connection' => $connection,
                'queue' => $queue,
                'duration_ms' => round($durationMs, 2),
            ]);
        }
    }

    public function recordQueueFailure(
        string $jobName,
        string $connection,
        ?string $queue,
        Throwable $throwable
    ): void {
        if (! $this->metricsEnabled()) {
            $this->logger->error('Queue job failed', [
                'job' => $jobName,
                'connection' => $connection,
                'queue' => $queue,
                'exception' => $throwable->getMessage(),
            ]);
            return;
        }

        $tags = array_filter([
            'job' => $this->normalizeIdentifier($jobName),
            'connection' => $this->normalizeIdentifier($connection),
            'queue' => $queue ? $this->normalizeIdentifier($queue) : null,
        ]);

        $this->metrics->increment('queue.job.failed', $tags);
        $this->prometheus?->incrementCounter('queue_job_failed_total', $tags);
        $this->prometheus?->incrementCounter(
            'queue_job_failed_total',
            [
                'job' => '__all__',
                'connection' => $tags['connection'] ?? 'default',
                'queue' => $tags['queue'] ?? 'default',
            ]
        );

        $this->logger->error('Queue job failed', [
            'job' => $jobName,
            'connection' => $connection,
            'queue' => $queue,
            'exception' => $throwable->getMessage(),
        ]);
    }

    public function recordQueueLag(string $jobName, string $connection, ?string $queue, float $lagSeconds): void
    {
        if (! $this->metricsEnabled()) {
            return;
        }

        $labels = array_filter([
            'job' => $this->normalizeIdentifier($jobName),
            'connection' => $this->normalizeIdentifier($connection),
            'queue' => $queue ? $this->normalizeIdentifier($queue) : null,
        ]);

        $this->prometheus?->setGauge('queue_lag_seconds', $lagSeconds, $labels);
        $this->prometheus?->setGauge(
            'queue_lag_seconds',
            $lagSeconds,
            [
                'job' => '__all__',
                'connection' => $labels['connection'] ?? 'default',
                'queue' => $labels['queue'] ?? 'default',
            ]
        );
    }

    public function recordDatabaseQuery(string $connection, string $sql, float $durationMs): void
    {
        if (! $this->metricsEnabled()) {
            return;
        }

        $queryType = Str::of($sql)->trim()->explode(' ')->first() ?? 'query';
        $normalizedType = $this->normalizeIdentifier(Str::lower($queryType));
        $labels = [
            'connection' => $this->normalizeIdentifier($connection),
            'type' => $normalizedType,
        ];

        $this->prometheus?->observeHistogram('database_query_duration_seconds', $durationMs / 1000.0, $labels);
        $this->prometheus?->incrementCounter('database_query_total', $labels);
        $this->prometheus?->observeHistogram(
            'database_query_duration_seconds',
            $durationMs / 1000.0,
            [
                'connection' => '__all__',
                'type' => $normalizedType,
            ]
        );
        $this->prometheus?->incrementCounter(
            'database_query_total',
            [
                'connection' => '__all__',
                'type' => $normalizedType,
            ]
        );

        $threshold = Arr::get($this->config, 'database.slow_query_threshold_ms', 0.0);
        if ($threshold > 0 && $durationMs >= $threshold) {
            $this->logger->warning('Slow database query detected', [
                'connection' => $connection,
                'type' => $queryType,
                'duration_ms' => round($durationMs, 2),
                'sql' => Str::limit($sql, 400),
            ]);
        }
    }

    public function recordMobileHttpRequest(array $payload): void
    {
        if (! $this->metricsEnabled()) {
            return;
        }

        $method = Str::lower((string) ($payload['method'] ?? 'get'));
        $status = (int) ($payload['status_code'] ?? 0);
        $route = $this->normalizeIdentifier((string) ($payload['route'] ?? $payload['path'] ?? 'mobile'));
        $durationMs = (float) ($payload['duration_ms'] ?? 0.0);
        $statusClass = $status > 0 ? sprintf('%dxx', (int) floor($status / 100)) : 'unknown';

        $this->prometheus?->observeHistogram(
            'mobile_http_request_duration_seconds',
            max($durationMs, 0.0) / 1000.0,
            [
                'route' => $route,
                'method' => $method,
                'status_class' => $statusClass,
            ]
        );
        $this->prometheus?->incrementCounter(
            'mobile_http_requests_total',
            [
                'route' => $route,
                'method' => $method,
                'status_class' => $statusClass,
            ]
        );

        if ($status >= 500 || $status === 0) {
            $this->prometheus?->incrementCounter(
                'mobile_http_requests_failed_total',
                [
                    'route' => $route,
                    'method' => $method,
                    'status_class' => $statusClass,
                ]
            );
        }
    }

    public function metricsEnabled(): bool
    {
        return (bool) Arr::get($this->config, 'metrics.enabled', true);
    }

    private function normalizeIdentifier(string $value): string
    {
        $sanitized = Str::of($value)
            ->replaceMatches('/[^A-Za-z0-9_\.\-\/]/', '_')
            ->trim('_');

        return $sanitized->isEmpty() ? 'unknown' : (string) $sanitized;
    }

    private function recordHttpPrometheusMetrics(
        float $durationMs,
        string $route,
        string $method,
        string $statusClass,
        bool $isError
    ): void {
        if ($this->prometheus === null) {
            return;
        }

        $durationSeconds = $durationMs / 1000.0;

        $labels = [
            'route' => $route,
            'method' => $method,
            'status_class' => $statusClass,
        ];
        $aggregateLabels = [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => $statusClass,
        ];
        $allStatusLabels = [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ];

        $this->prometheus->observeHistogram('http_request_duration_seconds', $durationSeconds, $labels);
        $this->prometheus->observeHistogram('http_request_duration_seconds', $durationSeconds, $aggregateLabels);
        $this->prometheus->observeHistogram('http_request_duration_seconds', $durationSeconds, $allStatusLabels);

        $this->prometheus->incrementCounter('http_requests_total', $labels);
        $this->prometheus->incrementCounter('http_requests_total', $aggregateLabels);
        $this->prometheus->incrementCounter('http_requests_total', $allStatusLabels);

        if ($isError) {
            $this->prometheus->incrementCounter('http_requests_errors_total', $labels);
            $this->prometheus->incrementCounter('http_requests_errors_total', $aggregateLabels);
            $this->prometheus->incrementCounter('http_requests_errors_total', $allStatusLabels);
        }
    }
}
