<?php

return [
    'community' => [
        'generic' => [
            'subject' => 'New activity from :community',
            'preview' => 'Catch up on the latest conversations happening now.',
        ],
        'post_created' => [
            'subject' => ':member just posted in :community',
            'preview' => 'See what is new in your community feed.',
        ],
        'post_liked' => [
            'subject' => 'Your post is getting reactions in :community',
            'preview' => 'Members are engaging with your contribution.',
        ],
        'comment_created' => [
            'subject' => 'New replies waiting in :community',
            'preview' => 'Jump back into the discussion and keep it going.',
        ],
        'member_approved' => [
            'subject' => 'Welcome to :community!',
            'preview' => 'Start exploring, introduce yourself, and join the conversation.',
        ],
    ],
    'digest' => [
        'daily' => [
            'subject' => 'Your daily community digest',
            'preview' => 'Highlights from the last 24 hours in your communities.',
            'cta' => 'Open daily digest',
        ],
        'weekly' => [
            'subject' => 'This week in your community',
            'preview' => 'Top discussions and wins from the past week.',
            'cta' => 'Review weekly digest',
        ],
    ],
];
