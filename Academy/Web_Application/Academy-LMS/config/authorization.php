<?php

return [
    'default_role' => 'guest',

    'aliases' => [
        'student' => 'member',
        'instructor' => 'moderator',
        'admin' => 'admin',
        'super_admin' => 'admin',
        'owner' => 'owner',
        'moderator' => 'moderator',
        'member' => 'member',
    ],

    'matrix' => [
        'guest' => [
            'community.view:public',
        ],
        'member' => [
            'community.view',
            'community.post',
            'post.update:own',
        ],
        'moderator' => [
            'community.view',
            'community.post',
            'community.moderate',
            'post.update',
            'post.pin',
            'member.ban',
            'search.audit',
            'search.saved',
        ],
        'owner' => [
            'community.view',
            'community.post',
            'community.moderate',
            'post.update',
            'post.pin',
            'member.ban',
            'paywall.manage',
            'search.audit',
            'search.saved',
            'secrets.manage',
            'migration.plan.view',
        ],
        'admin' => ['*'],
    ],

    'resolvers' => [
        App\Support\Authorization\Resolvers\GlobalRoleResolver::class,
        App\Support\Authorization\Resolvers\OwnershipRoleResolver::class,
    ],
];
