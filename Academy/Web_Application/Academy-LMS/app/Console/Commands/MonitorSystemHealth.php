<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Notifications\SystemHealthAlert;
use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class MonitorSystemHealth extends Command
{
    protected $signature = 'observability:monitor-health';

    protected $description = 'Evaluate observability metrics and emit alerts when SLOs are violated.';

    public function __construct(
        private readonly PrometheusRegistry $metrics,
        private readonly QueueFactory $queues
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $config = config('observability.alerts', []);

        if (($config['enabled'] ?? true) !== true) {
            $this->info('Observability alerts are disabled.');

            return self::SUCCESS;
        }

        $issues = array_merge(
            $this->detectErrorRateIssues($config),
            $this->detectQueueIssues($config),
            $this->detectDiskIssues($config),
            $this->detectRedisIssues($config)
        );

        $cooldown = (int) ($config['cooldown_seconds'] ?? 900);
        $issuesToNotify = [];
        foreach ($issues as $issue) {
            $fingerprint = $issue['fingerprint'];
            if ($this->shouldNotify($fingerprint, $cooldown)) {
                $issuesToNotify[] = $issue;
            }
        }

        if (empty($issuesToNotify)) {
            return self::SUCCESS;
        }

        $meta = [
            'environment' => config('app.env'),
            'window_seconds' => $config['metric_window_seconds'] ?? null,
        ];

        $channels = $config['notification_channels'] ?? [];
        $email = Arr::get($channels, 'mail');
        $slack = Arr::get($channels, 'slack');

        if ($email) {
            Notification::route('mail', $email)->notify(new SystemHealthAlert($issuesToNotify, $meta, ['mail']));
        }

        if ($slack) {
            Notification::route('slack', $slack)->notify(new SystemHealthAlert($issuesToNotify, $meta, ['slack']));
        }

        Log::channel('structured')->critical('System health alert triggered', [
            'issues' => $issuesToNotify,
            'meta' => $meta,
        ]);

        $this->warn(sprintf('System health issues detected: %d', count($issuesToNotify)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detectErrorRateIssues(array $config): array
    {
        $window = max(60, (int) ($config['metric_window_seconds'] ?? 300));
        $threshold = max(0.0, (float) ($config['error_rate_threshold'] ?? 0.02));

        $total = $this->metrics->counterWindowSum('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], $window);
        $errors = $this->metrics->counterWindowSum('http_requests_errors_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], $window);

        if ($total <= 0.0) {
            return [];
        }

        $errorRate = $errors / $total;
        if ($errorRate < $threshold) {
            return [];
        }

        return [[
            'fingerprint' => 'http:error-rate',
            'type' => 'HTTP error rate',
            'message' => sprintf('HTTP error rate %.2f%% exceeded threshold of %.2f%%', $errorRate * 100, $threshold * 100),
            'context' => [
                'total_requests' => (int) round($total),
                'error_requests' => (int) round($errors),
                'error_rate' => sprintf('%.3f', $errorRate),
                'window_seconds' => $window,
            ],
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detectQueueIssues(array $config): array
    {
        $issues = [];
        $queues = $config['queues'] ?? [];
        $lagThreshold = max(0.0, (float) ($config['queue_lag_threshold_seconds'] ?? 60));
        $backlogThreshold = max(0, (int) ($config['queue_backlog_threshold'] ?? 250));

        foreach ($queues as $queueConfig) {
            $connection = $queueConfig['connection'] ?? 'redis';
            $queue = $queueConfig['name'] ?? 'default';

            try {
                $size = $this->queues->connection($connection)->size($queue);
            } catch (Throwable $exception) {
                Log::warning('Unable to inspect queue backlog', [
                    'connection' => $connection,
                    'queue' => $queue,
                    'exception' => $exception->getMessage(),
                ]);
                continue;
            }

            if ($size >= $backlogThreshold) {
                $issues[] = [
                    'fingerprint' => sprintf('queue:backlog:%s:%s', $connection, $queue),
                    'type' => 'Queue backlog',
                    'message' => sprintf('Queue %s/%s backlog of %d jobs exceeds threshold of %d', $connection, $queue, $size, $backlogThreshold),
                    'context' => [
                        'connection' => $connection,
                        'queue' => $queue,
                        'size' => $size,
                    ],
                ];
            }

            $lag = $this->metrics->getGaugeValue('queue_lag_seconds', [
                'job' => '__all__',
                'connection' => $this->normalizeMetricIdentifier($connection),
                'queue' => $this->normalizeMetricIdentifier($queue),
            ]);

            if ($lag !== null && $lag >= $lagThreshold) {
                $issues[] = [
                    'fingerprint' => sprintf('queue:lag:%s:%s', $connection, $queue),
                    'type' => 'Queue lag',
                    'message' => sprintf('Queue %s/%s lag %.1fs exceeds threshold of %.1fs', $connection, $queue, $lag, $lagThreshold),
                    'context' => [
                        'connection' => $connection,
                        'queue' => $queue,
                        'lag_seconds' => round($lag, 2),
                    ],
                ];
            }
        }

        return $issues;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detectDiskIssues(array $config): array
    {
        $path = $config['disk_path'] ?? base_path();
        $threshold = (float) ($config['disk_free_ratio_threshold'] ?? 0.1);
        if ($threshold <= 0) {
            return [];
        }

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if (! $total || $total <= 0) {
            return [];
        }

        $ratio = $free / $total;
        if ($ratio > $threshold) {
            return [];
        }

        return [[
            'fingerprint' => 'disk:free-space',
            'type' => 'Disk capacity',
            'message' => sprintf('Free disk space at %s is %.2f%% below threshold %.2f%%', $path, $ratio * 100, $threshold * 100),
            'context' => [
                'path' => $path,
                'free_ratio' => $ratio,
                'free_bytes' => $free,
                'total_bytes' => $total,
            ],
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detectRedisIssues(array $config): array
    {
        $connection = $config['redis_connection'] ?? null;
        if (! $connection) {
            return [];
        }

        try {
            $info = Redis::connection($connection)->info('memory');
        } catch (Throwable $exception) {
            Log::warning('Unable to inspect Redis memory usage', [
                'connection' => $connection,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        $used = (float) ($info['used_memory'] ?? 0.0);
        $max = (float) ($info['maxmemory'] ?? 0.0);

        if ($max <= 0.0) {
            $max = (float) ($info['total_system_memory'] ?? 0.0);
        }

        if ($max <= 0.0) {
            return [];
        }

        $ratio = $used / $max;
        $threshold = (float) ($config['redis_memory_ratio_threshold'] ?? 0.85);

        if ($ratio < $threshold) {
            return [];
        }

        return [[
            'fingerprint' => sprintf('redis:memory:%s', $connection),
            'type' => 'Redis memory',
            'message' => sprintf('Redis connection %s using %.2f%% of configured memory limit', $connection, $ratio * 100),
            'context' => [
                'connection' => $connection,
                'used_memory' => $used,
                'max_memory' => $max,
                'ratio' => $ratio,
            ],
        ]];
    }

    private function shouldNotify(string $fingerprint, int $cooldown): bool
    {
        if ($cooldown <= 0) {
            return true;
        }

        $cacheKey = sprintf('observability:alert:%s', Str::slug($fingerprint));
        $last = Cache::get($cacheKey);
        if ($last && Carbon::parse($last)->diffInSeconds(Carbon::now()) < $cooldown) {
            return false;
        }

        Cache::put($cacheKey, Carbon::now()->toIso8601String(), Carbon::now()->addSeconds($cooldown));

        return true;
    }

    private function normalizeMetricIdentifier(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[^A-Za-z0-9_\.\-]/', '_')
            ->trim('_')
            ->whenEmpty(fn () => 'default')
            ->toString();
    }
}
