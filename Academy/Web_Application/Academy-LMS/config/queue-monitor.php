<?php

return [
    'connection' => env('QUEUE_MONITOR_REDIS_CONNECTION', env('QUEUE_REDIS_CONNECTION', 'horizon')),

    'redis_prefix' => env('QUEUE_MONITOR_REDIS_PREFIX', null),

    'default_thresholds' => [
        'pending_jobs' => (int) env('QUEUE_MONITOR_DEFAULT_PENDING_THRESHOLD', 150),
        'reserved_jobs' => (int) env('QUEUE_MONITOR_DEFAULT_RESERVED_THRESHOLD', 150),
        'delayed_jobs' => (int) env('QUEUE_MONITOR_DEFAULT_DELAYED_THRESHOLD', 150),
        'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_DEFAULT_OLDEST_PENDING', 180),
        'oldest_reserved_seconds' => (int) env('QUEUE_MONITOR_DEFAULT_OLDEST_RESERVED', 180),
        'oldest_delayed_seconds' => (int) env('QUEUE_MONITOR_DEFAULT_OLDEST_DELAYED', 300),
        'backlog_delta_per_minute' => (float) env('QUEUE_MONITOR_DEFAULT_BACKLOG_DELTA', -5.0),
    ],

    'queues' => [
        'default' => [
            'thresholds' => [
                'pending_jobs' => (int) env('QUEUE_MONITOR_DEFAULT_QUEUE_PENDING', 200),
                'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_DEFAULT_QUEUE_OLDEST_PENDING', 240),
            ],
            'public_message' => 'Background processing is running slowly. Some updates may take longer than expected.',
        ],
        'notifications' => [
            'thresholds' => [
                'pending_jobs' => (int) env('QUEUE_MONITOR_NOTIFICATIONS_PENDING', 400),
                'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_NOTIFICATIONS_OLDEST_PENDING', 120),
            ],
            'public_message' => 'Notifications are delayed. You may receive alerts later than usual.',
        ],
        'media' => [
            'thresholds' => [
                'pending_jobs' => (int) env('QUEUE_MONITOR_MEDIA_PENDING', 150),
                'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_MEDIA_OLDEST_PENDING', 120),
            ],
            'public_message' => 'Media uploads are processing slowly. Newly uploaded videos or images may take extra time to appear.',
        ],
        'webhooks' => [
            'thresholds' => [
                'pending_jobs' => (int) env('QUEUE_MONITOR_WEBHOOKS_PENDING', 120),
                'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_WEBHOOKS_OLDEST_PENDING', 180),
            ],
            'public_message' => 'External integrations are catching up. Automation may be briefly delayed.',
        ],
        'search-index' => [
            'thresholds' => [
                'pending_jobs' => (int) env('QUEUE_MONITOR_SEARCH_PENDING', 120),
                'oldest_pending_seconds' => (int) env('QUEUE_MONITOR_SEARCH_OLDEST_PENDING', 180),
            ],
            'public_message' => 'Search indexing is backlogged. Recent updates may take longer to appear in results.',
        ],
    ],

    'retention_hours' => (int) env('QUEUE_MONITOR_RETENTION_HOURS', 168),
];
