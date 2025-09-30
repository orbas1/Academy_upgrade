<?php

return [
    'spa' => [
        'version' => '2024.10.0',
        'base_path' => env('COMMUNITY_ADMIN_SPA_BASE', '/admin/communities/app'),
        'feature_flags' => [
            'communities.management.enabled' => filter_var(env('FEATURE_COMMUNITIES_MANAGEMENT', true), FILTER_VALIDATE_BOOLEAN),
            'communities.moderation.enabled' => filter_var(env('FEATURE_COMMUNITIES_MODERATION', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'modules' => [
            [
                'key' => 'communities',
                'name' => 'Communities',
                'description' => 'Manage communities, membership health, and monetization.',
                'feature_flag' => 'communities.management.enabled',
                'permissions' => ['communities.manage'],
                'navigation' => [
                    ['label' => 'Communities', 'route' => 'communities.index', 'icon' => 'ph-users-three'],
                    ['label' => 'Growth Insights', 'route' => 'communities.insights', 'icon' => 'ph-trend-up'],
                ],
                'routes' => [
                    ['name' => 'communities.index', 'path' => '/communities', 'title' => 'Communities'],
                    ['name' => 'communities.show', 'path' => '/communities/:id', 'title' => 'Community detail'],
                    ['name' => 'communities.insights', 'path' => '/communities/:id/insights', 'title' => 'Growth insights'],
                ],
                'endpoints' => [
                    'index' => '/communities',
                    'show' => '/communities/{id}',
                    'members' => '/communities/{id}/members',
                    'metrics' => '/communities/{id}/metrics',
                    'feed' => '/communities/{id}/feed',
                    'create_post' => '/communities/{id}/posts',
                    'toggle_reaction' => '/communities/{id}/posts/{post}/reactions',
                ],
                'capabilities' => [
                    'feed_filters' => ['new', 'top', 'moderation'],
                    'composer' => ['attachments' => true, 'scheduling' => true, 'paywall' => true],
                    'reactions' => ['like', 'celebrate', 'insightful', 'support'],
                ],
            ],
            [
                'key' => 'moderation',
                'name' => 'Moderation',
                'description' => 'Review reports and enforce community policies.',
                'feature_flag' => 'communities.moderation.enabled',
                'permissions' => ['communities.moderate'],
                'navigation' => [
                    ['label' => 'Moderation Queue', 'route' => 'moderation.queue', 'icon' => 'ph-shield-check'],
                    ['label' => 'Appeals', 'route' => 'moderation.appeals', 'icon' => 'ph-chat-circle-dots'],
                ],
                'routes' => [
                    ['name' => 'moderation.queue', 'path' => '/moderation/queue', 'title' => 'Moderation queue'],
                    ['name' => 'moderation.appeals', 'path' => '/moderation/appeals', 'title' => 'Appeals'],
                ],
                'endpoints' => [
                    'queue' => '/moderation/reports',
                    'bulk_action' => '/moderation/reports/bulk',
                    'appeals' => '/moderation/appeals',
                ],
            ],
        ],
    ],
    'api' => [
        'base_url' => env('COMMUNITY_ADMIN_API_URL', '/api/v1/admin'),
        'manifest_endpoint' => env('COMMUNITY_ADMIN_MANIFEST_ENDPOINT', '/api/v1/admin/communities/modules'),
    ],
];
