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
                    'created_at',
                    'engagement',
                ],
                'filterableAttributes' => [
                    'community_id',
                    'topics',
                    'visibility',
                    'is_paid',
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
        ],
    ],
];
