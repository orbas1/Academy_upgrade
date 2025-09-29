<?php

return [
    'brand' => [
        'name' => env('APP_NAME', 'Academy'),
        'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
        'light_logo' => 'email/brand-light.svg',
        'dark_logo' => 'email/brand-dark.svg',
    ],
    'community' => [
        'default_locale' => 'en',
        'supported_locales' => ['en', 'es'],
        'preferences_url' => env('COMMUNITY_NOTIFICATION_PREFERENCES_URL', '/settings/notifications'),
        'events' => [
            'invite' => [
                'email_view' => 'email.community.invite',
            ],
            'approval' => [
                'email_view' => 'email.community.approval',
            ],
            'new_reply' => [
                'email_view' => 'email.community.new_reply',
            ],
            'mention' => [
                'email_view' => 'email.community.mention',
            ],
            'purchase_receipt' => [
                'email_view' => 'email.community.purchase_receipt',
            ],
            'reminder' => [
                'email_view' => 'email.community.reminder',
            ],
            'digest' => [
                'email_view' => 'email.community.digest',
            ],
        ],
    ],
];
