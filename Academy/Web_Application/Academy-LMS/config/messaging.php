<?php

return [
    'email' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'notifications@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Academy Communities'),
        ],
        'templates' => [
            'community.generic' => [
                'view' => 'emails.communities.event',
                'subject' => 'notifications.community.generic.subject',
                'preview' => 'notifications.community.generic.preview',
            ],
            'post.created' => [
                'view' => 'emails.communities.event',
                'subject' => 'notifications.community.post_created.subject',
                'preview' => 'notifications.community.post_created.preview',
            ],
            'post.liked' => [
                'view' => 'emails.communities.event',
                'subject' => 'notifications.community.post_liked.subject',
                'preview' => 'notifications.community.post_liked.preview',
            ],
            'comment.created' => [
                'view' => 'emails.communities.event',
                'subject' => 'notifications.community.comment_created.subject',
                'preview' => 'notifications.community.comment_created.preview',
            ],
            'member.approved' => [
                'view' => 'emails.communities.welcome',
                'subject' => 'notifications.community.member_approved.subject',
                'preview' => 'notifications.community.member_approved.preview',
            ],
            'digest.daily' => [
                'view' => 'emails.communities.digest',
                'subject' => 'notifications.digest.daily.subject',
                'preview' => 'notifications.digest.daily.preview',
            ],
            'digest.weekly' => [
                'view' => 'emails.communities.digest',
                'subject' => 'notifications.digest.weekly.subject',
                'preview' => 'notifications.digest.weekly.preview',
            ],
        ],
    ],

    'push' => [
        'default_provider' => env('PUSH_PROVIDER', 'fcm'),
        'providers' => [
            'fcm' => [
                'endpoint' => env('FCM_REST_ENDPOINT', 'https://fcm.googleapis.com/v1/projects/example/messages:send'),
                'token' => env('FCM_REST_TOKEN'),
                'timeout' => 5,
            ],
            'resend' => [
                'endpoint' => env('RESEND_PUSH_ENDPOINT'),
                'token' => env('RESEND_PUSH_TOKEN'),
                'timeout' => 5,
            ],
        ],
        'retries' => 3,
        'timeout' => 5,
    ],

    'digests' => [
        'frequencies' => [
            'daily' => 'P1D',
            'weekly' => 'P1W',
        ],
        'window_overrides' => [
            'weekly' => 'P7D',
        ],
        'max_items' => 25,
    ],

    'deep_links' => [
        'mobile_scheme' => env('MOBILE_DEEP_LINK_SCHEME', 'academy://'),
        'web_base_url' => env('APP_URL', 'https://example.com'),
        'events' => [
            'post.created' => [
                'path' => '/communities/{community}/posts/{post}',
            ],
            'post.liked' => [
                'path' => '/communities/{community}/posts/{post}',
            ],
            'comment.created' => [
                'path' => '/communities/{community}/posts/{post}?comment={comment}',
            ],
            'member.approved' => [
                'path' => '/communities/{community}',
            ],
            'digest.daily' => [
                'path' => '/communities/{community}/digest/daily',
            ],
            'digest.weekly' => [
                'path' => '/communities/{community}/digest/weekly',
            ],
        ],
    ],
];
