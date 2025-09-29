<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | The search driver allows future expansion (for example, Algolia or
    | Elasticsearch). For now, we default to Meilisearch which is aligned to the
    | real-time community discovery requirements outlined in the upgrade plan.
    */
    'driver' => env('SEARCH_DRIVER', 'meilisearch'),

    'meilisearch' => [
        /*
        |------------------------------------------------------------------
        | Connection Settings
        |------------------------------------------------------------------
        */
        'host' => env('MEILISEARCH_HOST', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'timeout' => (int) env('MEILISEARCH_TIMEOUT', 10),

        /*
        |------------------------------------------------------------------
        | Default Index Definitions
        |------------------------------------------------------------------
        |
        | Each index captures the core configuration required for the
        | communities-first experience. These settings feed the synchronisation
        | command and can be extended per environment using config overrides.
        */
        'indexes' => [
            'communities' => [
                'primaryKey' => 'id',
                'searchableAttributes' => [
                    'name',
                    'description',
                    'tags',
                    'location.city',
                    'location.country',
                ],
                'displayedAttributes' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'tags',
                    'member_count',
                    'online_count',
                    'tier_names',
                    'location',
                ],
                'filterableAttributes' => [
                    'visibility',
                    'tier_names',
                    'tags',
                    'location.country',
                    'is_featured',
                ],
                'sortableAttributes' => [
                    'member_count',
                    'online_count',
                    'recent_activity_at',
                    'created_at',
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'desc(member_count)',
                    'desc(online_count)',
                    'desc(recent_activity_at)',
                ],
                'stopWords' => [
                    'the', 'and', 'a', 'an', 'of', 'for', 'to', 'in',
                ],
                'synonyms' => [
                    'community' => ['group', 'circle', 'squad'],
                    'meetup' => ['event', 'gathering'],
                    'mentor' => ['coach', 'guide'],
                ],
            ],
            'posts' => [
                'primaryKey' => 'id',
                'searchableAttributes' => [
                    'title',
                    'body',
                    'author.name',
                    'topics',
                ],
                'displayedAttributes' => [
                    'id',
                    'community_id',
                    'title',
                    'excerpt',
                    'body',
                    'author',
                    'topics',
                    'paywall_tier_id',
                    'created_at',
                    'engagement',
                ],
                'filterableAttributes' => [
                    'community_id',
                    'topics',
                    'visibility',
                    'is_paid',
                    'paywall_tier_id',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'engagement.score',
                    'engagement.comment_count',
                    'engagement.reaction_count',
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'desc(engagement.score)',
                    'desc(engagement.comment_count)',
                ],
                'stopWords' => [],
                'synonyms' => [
                    'post' => ['thread', 'update'],
                    'like' => ['upvote', 'applause'],
                ],
            ],
            'members' => [
                'primaryKey' => 'id',
                'searchableAttributes' => [
                    'name',
                    'headline',
                    'about',
                    'skills',
                    'roles',
                    'location.country',
                    'location.city',
                ],
                'displayedAttributes' => [
                    'id',
                    'name',
                    'email',
                    'avatar_url',
                    'headline',
                    'roles',
                    'skills',
                    'location',
                    'joined_at',
                    'last_active_at',
                    'engagement',
                ],
                'filterableAttributes' => [
                    'roles',
                    'location.country',
                    'location.city',
                    'has_mentor_status',
                ],
                'sortableAttributes' => [
                    'joined_at',
                    'last_active_at',
                    'engagement.score',
                    'engagement.contribution_count',
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'desc(engagement.score)',
                ],
                'stopWords' => [],
                'synonyms' => [
                    'mentor' => ['coach', 'guide'],
                    'member' => ['participant', 'learner'],
                ],
            ],
        ],
    ],

    'visibility' => [
        'token_secret' => env('SEARCH_VISIBILITY_TOKEN_SECRET', env('APP_KEY')),
        'ttl' => (int) env('SEARCH_VISIBILITY_TOKEN_TTL', 900),
    ],

    'scopes' => [
        'communities' => [
            'index' => 'communities',
            'allowed_filters' => [
                'visibility',
                'tags',
                'tier_names',
                'location.country',
                'location.city',
                'is_featured',
            ],
            'admin_allowed_filters' => [
                'moderation.flag_status',
                'owner_id',
            ],
            'allowed_sorts' => [
                'member_count',
                'online_count',
                'recent_activity_at',
                'created_at',
            ],
            'default_sort' => 'recent_activity_at:desc',
            'facets' => [
                'visibility',
                'location.country',
                'tags',
                'tier_names',
            ],
        ],
        'posts' => [
            'index' => 'posts',
            'allowed_filters' => [
                'community_id',
                'topics',
                'visibility',
                'is_paid',
                'author.role',
                'type',
            ],
            'admin_allowed_filters' => [
                'moderation.flag_status',
                'moderation.reason_code',
                'author_id',
            ],
            'allowed_sorts' => [
                'created_at',
                'engagement.score',
                'engagement.comment_count',
                'engagement.reaction_count',
            ],
            'default_sort' => 'created_at:desc',
            'facets' => [
                'community_id',
                'topics',
                'visibility',
                'is_paid',
                'author.role',
                'type',
            ],
        ],
        'members' => [
            'index' => 'members',
            'allowed_filters' => [
                'roles',
                'location.country',
                'location.city',
                'has_mentor_status',
                'badges',
            ],
            'admin_allowed_filters' => [
                'status',
                'is_suspended',
            ],
            'allowed_sorts' => [
                'joined_at',
                'last_active_at',
                'engagement.score',
                'engagement.contribution_count',
            ],
            'default_sort' => 'last_active_at:desc',
            'facets' => [
                'roles',
                'location.country',
                'location.city',
                'has_mentor_status',
            ],
        ],
    ],

    'sync' => [
        'resources' => [
            'communities' => [
                'index' => 'communities',
                'data_source' => App\Domain\Search\DataSources\CommunityDataSource::class,
                'transformer' => App\Domain\Search\Transformers\CommunitySearchTransformer::class,
                'model' => App\Domain\Search\Models\CommunityStub::class,
                'chunk_size' => (int) env('SEARCH_SYNC_CHUNK_COMMUNITIES', 500),
            ],
            'posts' => [
                'index' => 'posts',
                'data_source' => App\Domain\Search\DataSources\PostDataSource::class,
                'transformer' => App\Domain\Search\Transformers\PostSearchTransformer::class,
                'model' => App\Domain\Search\Models\PostStub::class,
                'chunk_size' => (int) env('SEARCH_SYNC_CHUNK_POSTS', 500),
            ],
            'members' => [
                'index' => 'members',
                'data_source' => App\Domain\Search\DataSources\MemberDataSource::class,
                'transformer' => App\Domain\Search\Transformers\MemberSearchTransformer::class,
                'model' => App\Models\User::class,
                'chunk_size' => (int) env('SEARCH_SYNC_CHUNK_MEMBERS', 500),
            ],
        ],
    ],
];
