<?php

declare(strict_types=1);

namespace App\Support\Observability;

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

        $this->logger->error('Queue job failed', [
            'job' => $jobName,
            'connection' => $connection,
            'queue' => $queue,
            'exception' => $throwable->getMessage(),
        ]);
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
}
