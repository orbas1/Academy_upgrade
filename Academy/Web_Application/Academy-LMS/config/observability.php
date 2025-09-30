<?php

return [
    'metrics' => [
        'enabled' => env('OBS_METRICS_ENABLED', true),
        'host' => env('OBS_METRICS_HOST', '127.0.0.1'),
        'port' => (int) env('OBS_METRICS_PORT', 8125),
        'prefix' => env('OBS_METRICS_PREFIX', 'academy'),
        'timeout' => (float) env('OBS_METRICS_SOCKET_TIMEOUT', 0.2),
        'default_tags' => [
            'app' => env('APP_NAME', 'academy'),
            'env' => env('APP_ENV', 'production'),
        ],
    ],
    'http' => [
        'slow_request_threshold_ms' => (float) env('OBS_HTTP_SLOW_THRESHOLD_MS', 1200.0),
    ],
    'queue' => [
        'slow_job_threshold_ms' => (float) env('OBS_QUEUE_SLOW_THRESHOLD_MS', 2500.0),
    ],
    'database' => [
        'slow_query_threshold_ms' => (float) env('OBS_DB_SLOW_THRESHOLD_MS', 200.0),
    ],
    'prometheus' => [
        'enabled' => env('OBS_PROMETHEUS_ENABLED', true),
        'store' => env('OBS_PROMETHEUS_CACHE_STORE', null),
        'prefix' => env('OBS_PROMETHEUS_PREFIX', 'observability'),
        'retention_seconds' => (int) env('OBS_PROMETHEUS_RETENTION_SECONDS', 86_400),
        'window_retention_seconds' => (int) env('OBS_PROMETHEUS_WINDOW_RETENTION_SECONDS', 7_200),
        'default_buckets' => array_map(
            static fn ($value) => (float) $value,
            explode(',', env('OBS_PROMETHEUS_DEFAULT_BUCKETS', '0.005,0.01,0.025,0.05,0.1,0.25,0.5,1,2.5,5,10'))
        ),
        'lock_seconds' => (int) env('OBS_PROMETHEUS_LOCK_SECONDS', 5),
        'lock_wait_seconds' => (int) env('OBS_PROMETHEUS_LOCK_WAIT_SECONDS', 3),
        'auth_token' => env('OBS_PROMETHEUS_TOKEN', null),
    ],
    'logging' => [
        'channel' => env('OBS_LOG_CHANNEL', null),
        'share_context' => env('OBS_LOG_SHARE_CONTEXT', true),
    ],
    'alerts' => [
        'enabled' => env('OBS_ALERTS_ENABLED', true),
        'error_rate_threshold' => (float) env('OBS_ALERT_ERROR_RATE_THRESHOLD', 0.02),
        'metric_window_seconds' => (int) env('OBS_ALERT_METRIC_WINDOW_SECONDS', 300),
        'queue_backlog_threshold' => (int) env('OBS_ALERT_QUEUE_BACKLOG_THRESHOLD', 250),
        'queue_lag_threshold_seconds' => (int) env('OBS_ALERT_QUEUE_LAG_THRESHOLD', 60),
        'queues' => array_values(array_filter(
            array_map(
                static function (string $queue): array {
                    $parts = array_map('trim', explode(':', $queue, 2));

                    return [
                        'connection' => $parts[0] !== '' ? $parts[0] : 'redis',
                        'name' => $parts[1] ?? 'default',
                    ];
                },
                preg_split('/[,\s]+/', (string) env('OBS_ALERT_QUEUE_MAP', 'redis:default'), -1, PREG_SPLIT_NO_EMPTY) ?: []
            ),
            static fn (array $queue) => ! empty($queue['name'])
        )),
        'disk_path' => env('OBS_ALERT_DISK_PATH', base_path()),
        'disk_free_ratio_threshold' => (float) env('OBS_ALERT_DISK_FREE_RATIO_THRESHOLD', 0.1),
        'redis_connection' => env('OBS_ALERT_REDIS_CONNECTION', null),
        'redis_memory_ratio_threshold' => (float) env('OBS_ALERT_REDIS_MEMORY_RATIO_THRESHOLD', 0.85),
        'notification_channels' => [
            'mail' => env('OBS_ALERT_EMAIL', null),
            'slack' => env('OBS_ALERT_SLACK_WEBHOOK', env('SLACK_HORIZON_WEBHOOK', null)),
        ],
        'cooldown_seconds' => (int) env('OBS_ALERT_COOLDOWN_SECONDS', 900),
    ],
];
