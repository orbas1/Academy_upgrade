<?php

use Illuminate\Support\Str;

$queueConnection = env('QUEUE_REDIS_CONNECTION', 'horizon');
$defaultQueue = env('QUEUE_DEFAULT', 'default');
$notificationsQueue = env('QUEUE_NOTIFICATIONS', 'notifications');
$mediaQueue = env('QUEUE_MEDIA', 'media');
$webhookQueue = env('QUEUE_WEBHOOKS', 'webhooks');
$searchQueue = env('QUEUE_SEARCH', 'search-index');

return [
    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => $queueConnection,

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        $queueConnection.':'.$defaultQueue => 60,
        $queueConnection.':'.$notificationsQueue => 30,
        $queueConnection.':'.$mediaQueue => 30,
        $queueConnection.':'.$webhookQueue => 30,
        $queueConnection.':'.$searchQueue => 45,
    ],

    'trim' => [
        'recent' => (int) env('HORIZON_TRIM_RECENT', 120),
        'pending' => (int) env('HORIZON_TRIM_PENDING', 120),
        'completed' => (int) env('HORIZON_TRIM_COMPLETED', 120),
        'recent_failed' => (int) env('HORIZON_TRIM_RECENT_FAILED', 10080),
        'failed' => (int) env('HORIZON_TRIM_FAILED', 10080),
        'monitored' => (int) env('HORIZON_TRIM_MONITORED', 10080),
    ],

    'silenced' => [],

    'silenced_tags' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => (int) env('HORIZON_METRICS_TRIM_JOB', 72),
            'queue' => (int) env('HORIZON_METRICS_TRIM_QUEUE', 72),
        ],
    ],

    'fast_termination' => env('HORIZON_FAST_TERMINATION', true),

    'memory_limit' => (int) env('HORIZON_MEMORY_LIMIT', env('HORIZON_WORKER_MEMORY', 256)),

    'defaults' => [
        'notifications' => [
            'connection' => $queueConnection,
            'queue' => [
                $notificationsQueue => (int) env('HORIZON_NOTIFICATIONS_CONCURRENCY', 20),
            ],
            'balance' => env('HORIZON_BALANCE', 'auto'),
            'autoScalingStrategy' => env('HORIZON_NOTIFICATIONS_AUTOSCALE_STRATEGY', 'time'),
            'minProcesses' => (int) env('HORIZON_NOTIFICATIONS_MIN_PROCESSES', 2),
            'maxProcesses' => (int) env('HORIZON_NOTIFICATIONS_MAX_PROCESSES', 20),
            'maxTime' => (int) env('HORIZON_MAX_TIME', 0),
            'maxJobs' => (int) env('HORIZON_MAX_JOBS', 0),
            'memory' => (int) env('HORIZON_WORKER_MEMORY', 256),
            'tries' => (int) env('HORIZON_NOTIFICATIONS_TRIES', env('HORIZON_WORKER_TRIES', 3)),
            'timeout' => (int) env('HORIZON_NOTIFICATIONS_TIMEOUT', env('HORIZON_WORKER_TIMEOUT', 120)),
            'nice' => 0,
        ],

        'media' => [
            'connection' => $queueConnection,
            'queue' => [
                $mediaQueue => (int) env('HORIZON_MEDIA_CONCURRENCY', 12),
            ],
            'balance' => env('HORIZON_BALANCE', 'auto'),
            'autoScalingStrategy' => env('HORIZON_MEDIA_AUTOSCALE_STRATEGY', 'time'),
            'minProcesses' => (int) env('HORIZON_MEDIA_MIN_PROCESSES', 1),
            'maxProcesses' => (int) env('HORIZON_MEDIA_MAX_PROCESSES', 12),
            'maxTime' => (int) env('HORIZON_MAX_TIME', 0),
            'maxJobs' => (int) env('HORIZON_MAX_JOBS', 0),
            'memory' => (int) env('HORIZON_WORKER_MEMORY', 256),
            'tries' => (int) env('HORIZON_MEDIA_TRIES', env('HORIZON_WORKER_TRIES', 3)),
            'timeout' => (int) env('HORIZON_MEDIA_TIMEOUT', env('HORIZON_WORKER_TIMEOUT', 300)),
            'nice' => 0,
        ],

        'webhooks' => [
            'connection' => $queueConnection,
            'queue' => [
                $webhookQueue => (int) env('HORIZON_WEBHOOKS_CONCURRENCY', 6),
            ],
            'balance' => env('HORIZON_BALANCE', 'auto'),
            'autoScalingStrategy' => env('HORIZON_WEBHOOKS_AUTOSCALE_STRATEGY', 'time'),
            'minProcesses' => (int) env('HORIZON_WEBHOOKS_MIN_PROCESSES', 1),
            'maxProcesses' => (int) env('HORIZON_WEBHOOKS_MAX_PROCESSES', 8),
            'maxTime' => (int) env('HORIZON_MAX_TIME', 0),
            'maxJobs' => (int) env('HORIZON_MAX_JOBS', 0),
            'memory' => (int) env('HORIZON_WORKER_MEMORY', 256),
            'tries' => (int) env('HORIZON_WEBHOOKS_TRIES', env('HORIZON_WORKER_TRIES', 5)),
            'timeout' => (int) env('HORIZON_WEBHOOKS_TIMEOUT', env('HORIZON_WORKER_TIMEOUT', 120)),
            'nice' => 0,
        ],

        'search' => [
            'connection' => $queueConnection,
            'queue' => [
                $searchQueue => (int) env('HORIZON_SEARCH_CONCURRENCY', 4),
            ],
            'balance' => env('HORIZON_BALANCE', 'auto'),
            'autoScalingStrategy' => env('HORIZON_SEARCH_AUTOSCALE_STRATEGY', 'time'),
            'minProcesses' => (int) env('HORIZON_SEARCH_MIN_PROCESSES', 1),
            'maxProcesses' => (int) env('HORIZON_SEARCH_MAX_PROCESSES', 8),
            'maxTime' => (int) env('HORIZON_MAX_TIME', 0),
            'maxJobs' => (int) env('HORIZON_MAX_JOBS', 0),
            'memory' => (int) env('HORIZON_WORKER_MEMORY', 256),
            'tries' => (int) env('HORIZON_SEARCH_TRIES', env('HORIZON_WORKER_TRIES', 3)),
            'timeout' => (int) env('HORIZON_SEARCH_TIMEOUT', env('HORIZON_WORKER_TIMEOUT', 120)),
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'notifications' => [
                'balanceMaxShift' => (int) env('HORIZON_BALANCE_MAX_SHIFT', 1),
                'balanceCooldown' => (int) env('HORIZON_BALANCE_COOLDOWN', 3),
            ],
            'media' => [
                'balanceMaxShift' => (int) env('HORIZON_BALANCE_MAX_SHIFT', 1),
                'balanceCooldown' => (int) env('HORIZON_BALANCE_COOLDOWN', 3),
            ],
            'webhooks' => [
                'balanceMaxShift' => (int) env('HORIZON_BALANCE_MAX_SHIFT', 1),
                'balanceCooldown' => (int) env('HORIZON_BALANCE_COOLDOWN', 3),
            ],
            'search' => [
                'balanceMaxShift' => (int) env('HORIZON_BALANCE_MAX_SHIFT', 1),
                'balanceCooldown' => (int) env('HORIZON_BALANCE_COOLDOWN', 3),
            ],
        ],

        'staging' => [
            'notifications' => [
                'minProcesses' => 1,
                'maxProcesses' => max(2, (int) env('HORIZON_NOTIFICATIONS_MIN_PROCESSES', 2)),
            ],
            'media' => [
                'minProcesses' => 1,
                'maxProcesses' => max(2, (int) env('HORIZON_MEDIA_MIN_PROCESSES', 1)),
            ],
            'webhooks' => [
                'minProcesses' => 1,
                'maxProcesses' => max(2, (int) env('HORIZON_WEBHOOKS_MIN_PROCESSES', 1)),
            ],
            'search' => [
                'minProcesses' => 1,
                'maxProcesses' => max(2, (int) env('HORIZON_SEARCH_MIN_PROCESSES', 1)),
            ],
        ],

        'local' => [
            'dev' => [
                'connection' => $queueConnection,
                'queue' => [
                    $defaultQueue,
                    $notificationsQueue,
                    $mediaQueue,
                    $webhookQueue,
                    $searchQueue,
                ],
                'balance' => 'simple',
                'maxProcesses' => 3,
            ],
        ],
    ],
];
