<?php

return [
    'sanitizer' => [
        'allowed_tags' => '<p><a><ul><ol><li><strong><em><blockquote><code><pre><img><h1><h2><h3><h4><h5><h6><br><hr>',
    ],
    'maintenance' => [
        'chunk' => env('COMMUNITIES_MAINTENANCE_CHUNK', 200),
        'tables' => [
            'community_posts',
            'community_post_comments',
            'community_members',
            'community_post_likes',
            'community_comment_likes',
            'community_points_ledger',
        ],
    ],
    'automation' => [
        'auto_archive' => [
            'inactive_days' => env('COMMUNITIES_AUTO_ARCHIVE_INACTIVE_DAYS', 45),
            'recent_activity_days' => env('COMMUNITIES_AUTO_ARCHIVE_RECENT_ACTIVITY_DAYS', 7),
            'chunk' => env('COMMUNITIES_AUTO_ARCHIVE_CHUNK', 500),
        ],
        'welcome_template' => env('COMMUNITIES_WELCOME_TEMPLATE', 'Welcome to %community_name%! Let us know what you are hoping to achieve.'),
        'health' => [
            'queue_threshold' => env('COMMUNITIES_QUEUE_THRESHOLD', 25),
            'error_rate_threshold' => env('COMMUNITIES_ERROR_RATE_THRESHOLD', 0.05),
            'notification_webhook' => env('COMMUNITIES_ALERT_WEBHOOK', null),
        ],
    ],
    'leaderboards' => [
        'default_limit' => env('COMMUNITIES_LEADERBOARD_LIMIT', 50),
        'refresh_minutes' => env('COMMUNITIES_LEADERBOARD_REFRESH_MINUTES', 30),
        'cache_seconds' => env('COMMUNITIES_LEADERBOARD_CACHE_SECONDS', 300),
    ],
    'paywall' => [
        'default_trial_days' => env('COMMUNITIES_PAYWALL_DEFAULT_TRIAL_DAYS', 7),
    ],
];
