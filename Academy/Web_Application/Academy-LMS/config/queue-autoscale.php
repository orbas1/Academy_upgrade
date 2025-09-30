<?php

return [
    'systemd_binary' => env('SYSTEMD_BINARY', '/bin/systemctl'),
    'reload_command' => env('SYSTEMD_RELOAD_COMMAND', 'reload-or-restart'),
    'require_root' => env('SYSTEMD_REQUIRE_ROOT', true),
    'default_min_processes' => (int) env('HORIZON_AUTOSCALE_MIN_DEFAULT', 1),
    'default_max_processes' => (int) env('HORIZON_AUTOSCALE_MAX_DEFAULT', 16),
    'queues' => [
        'notifications' => [
            'queue' => env('HORIZON_NOTIFICATIONS_QUEUE', 'notifications'),
            'service' => env('HORIZON_NOTIFICATIONS_SERVICE', 'horizon@notifications.service'),
            'env_file' => env('HORIZON_NOTIFICATIONS_ENV_FILE', '/etc/academy/horizon/notifications.env'),
            'min_processes' => (int) env('HORIZON_NOTIFICATIONS_MIN_PROCESSES', 2),
            'max_processes' => (int) env('HORIZON_NOTIFICATIONS_MAX_PROCESSES', 24),
            'scale' => [
                ['pending' => 0, 'processes' => 2],
                ['pending' => 150, 'processes' => 4],
                ['pending' => 400, 'processes' => 8, 'oldest_pending' => 90],
                ['pending' => 800, 'processes' => 16, 'oldest_pending' => 120],
            ],
        ],
        'media' => [
            'queue' => env('HORIZON_MEDIA_QUEUE', 'media'),
            'service' => env('HORIZON_MEDIA_SERVICE', 'horizon@media.service'),
            'env_file' => env('HORIZON_MEDIA_ENV_FILE', '/etc/academy/horizon/media.env'),
            'min_processes' => (int) env('HORIZON_MEDIA_MIN_PROCESSES', 1),
            'max_processes' => (int) env('HORIZON_MEDIA_MAX_PROCESSES', 12),
            'scale' => [
                ['pending' => 0, 'processes' => 1],
                ['pending' => 50, 'processes' => 3],
                ['pending' => 120, 'processes' => 6, 'oldest_pending' => 120],
                ['pending' => 240, 'processes' => 10, 'oldest_pending' => 180],
            ],
        ],
        'webhooks' => [
            'queue' => env('HORIZON_WEBHOOK_QUEUE', 'webhooks'),
            'service' => env('HORIZON_WEBHOOK_SERVICE', 'horizon@webhooks.service'),
            'env_file' => env('HORIZON_WEBHOOK_ENV_FILE', '/etc/academy/horizon/webhooks.env'),
            'min_processes' => (int) env('HORIZON_WEBHOOK_MIN_PROCESSES', 1),
            'max_processes' => (int) env('HORIZON_WEBHOOK_MAX_PROCESSES', 10),
            'scale' => [
                ['pending' => 0, 'processes' => 1],
                ['pending' => 60, 'processes' => 3],
                ['pending' => 120, 'processes' => 6],
            ],
        ],
        'search-index' => [
            'queue' => env('HORIZON_SEARCH_QUEUE', 'search-index'),
            'service' => env('HORIZON_SEARCH_SERVICE', 'horizon@search-index.service'),
            'env_file' => env('HORIZON_SEARCH_ENV_FILE', '/etc/academy/horizon/search-index.env'),
            'min_processes' => (int) env('HORIZON_SEARCH_MIN_PROCESSES', 1),
            'max_processes' => (int) env('HORIZON_SEARCH_MAX_PROCESSES', 8),
            'scale' => [
                ['pending' => 0, 'processes' => 1],
                ['pending' => 40, 'processes' => 2],
                ['pending' => 80, 'processes' => 4, 'oldest_pending' => 120],
            ],
        ],
        'default' => [
            'queue' => env('HORIZON_DEFAULT_QUEUE', 'default'),
            'service' => env('HORIZON_DEFAULT_SERVICE', 'horizon@default.service'),
            'env_file' => env('HORIZON_DEFAULT_ENV_FILE', '/etc/academy/horizon/default.env'),
            'min_processes' => (int) env('HORIZON_DEFAULT_MIN_PROCESSES', 1),
            'max_processes' => (int) env('HORIZON_DEFAULT_MAX_PROCESSES', 12),
            'scale' => [
                ['pending' => 0, 'processes' => 1],
                ['pending' => 60, 'processes' => 3],
                ['pending' => 150, 'processes' => 6, 'oldest_pending' => 180],
            ],
        ],
    ],
];
