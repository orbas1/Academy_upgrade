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
    'logging' => [
        'channel' => env('OBS_LOG_CHANNEL'),
        'share_context' => env('OBS_LOG_SHARE_CONTEXT', true),
    ],
];
