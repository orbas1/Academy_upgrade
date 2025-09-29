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
];
