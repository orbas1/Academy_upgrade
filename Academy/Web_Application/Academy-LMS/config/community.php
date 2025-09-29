<?php

declare(strict_types=1);

use App\Enums\Community\CommunityFollowableType;
use App\Enums\Community\CommunityJoinPolicy;
use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use App\Enums\Community\CommunityPaywallAccessGrantedBy;
use App\Enums\Community\CommunityPointsEvent;
use App\Enums\Community\CommunityPostType;
use App\Enums\Community\CommunityPostVisibility;
use App\Enums\Community\CommunitySubscriptionInterval;
use App\Enums\Community\CommunitySubscriptionStatus;
use App\Enums\Community\CommunityVisibility;

return [
    'tables' => [
        'categories' => 'community_categories',
        'communities' => 'communities',
        'members' => 'community_members',
        'posts' => 'community_posts',
        'comments' => 'community_comments',
        'likes' => 'community_likes',
        'follows' => 'community_follows',
        'leaderboards' => 'community_leaderboards',
        'levels' => 'community_levels',
        'points_rules' => 'community_points_rules',
        'admin_settings' => 'community_admin_settings',
        'geo_places' => 'community_geo_places',
        'subscription_tiers' => 'community_subscription_tiers',
        'subscriptions' => 'community_subscriptions',
        'paywall_access' => 'community_paywall_access',
        'single_purchases' => 'community_single_purchases',
    ],

    'columns' => [
        'primary_key' => 'id',
        'foreign_key_suffix' => '_id',
        'tenant_key' => 'tenant_id',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
        'soft_delete' => 'deleted_at',
        'online_indicator' => 'last_seen_at',
    ],

    'json_columns' => [
        'communities' => ['links'],
        'members' => ['badges'],
        'posts' => ['media'],
        'leaderboards' => ['data'],
        'levels' => ['perks'],
        'points_rules' => ['metadata'],
        'admin_settings' => ['settings'],
        'geo_places' => ['geo'],
        'subscription_tiers' => ['benefits'],
    ],

    'audit_columns' => [
        'communities' => ['created_by', 'updated_by'],
    ],

    'defaults' => [
        'members' => [
            'points' => 0,
            'level' => 1,
            'is_online' => false,
        ],
        'posts' => [
            'is_pinned' => false,
            'is_locked' => false,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
        ],
        'subscription_tiers' => [
            'currency' => 'USD',
            'interval' => CommunitySubscriptionInterval::MONTH->value,
        ],
    ],

    'enums' => [
        'communities.visibility' => CommunityVisibility::class,
        'communities.join_policy' => CommunityJoinPolicy::class,
        'members.role' => CommunityMemberRole::class,
        'members.status' => CommunityMemberStatus::class,
        'posts.type' => CommunityPostType::class,
        'posts.visibility' => CommunityPostVisibility::class,
        'follows.type' => CommunityFollowableType::class,
        'leaderboards.period' => CommunityLeaderboardPeriod::class,
        'points.event' => CommunityPointsEvent::class,
        'subscriptions.status' => CommunitySubscriptionStatus::class,
        'subscription_tiers.interval' => CommunitySubscriptionInterval::class,
        'paywall_access.granted_by' => CommunityPaywallAccessGrantedBy::class,
    ],

    'audit' => [
        'soft_deletable_tables' => [
            'posts',
            'comments',
            'members',
        ],
        'immutable_tables' => [
            'leaderboards',
        ],
    ],

    'naming_rules' => [
        'tables' => 'snake_case plural nouns',
        'columns' => 'snake_case singular nouns',
        'pivots' => 'alphabetical order snake_case',
    ],
];
