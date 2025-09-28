<?php

return [
    'query_cache' => [
        'enabled' => env('PERFORMANCE_QUERY_CACHE_ENABLED', env('APP_ENV') !== 'local'),
        'store' => env('PERFORMANCE_QUERY_CACHE_STORE'),
        'default_ttl' => (int) env('PERFORMANCE_QUERY_CACHE_TTL', 300),
        'allow_on_debug' => (bool) env('PERFORMANCE_QUERY_CACHE_ALLOW_DEBUG', false),
    ],

    'http_cache' => [
        'enabled' => env('PERFORMANCE_HTTP_CACHE_ENABLED', true),
        'public_max_age' => (int) env('PERFORMANCE_HTTP_CACHE_MAX_AGE', 60),
        'stale_while_revalidate' => (int) env('PERFORMANCE_HTTP_CACHE_STALE', 120),
        'vary' => array_filter(array_map('trim', explode(',', env('PERFORMANCE_HTTP_CACHE_VARY', 'Accept-Encoding, Accept-Language')))),
        'etag' => (bool) env('PERFORMANCE_HTTP_CACHE_ETAG', true),
        'skip_authenticated' => (bool) env('PERFORMANCE_HTTP_CACHE_SKIP_AUTH', true),
        'skip_when_has_cookie' => (bool) env('PERFORMANCE_HTTP_CACHE_SKIP_COOKIE', true),
        'status_codes' => [200],
        'rules' => [
            ['name' => 'frontend.*'],
            ['name' => 'pages.*'],
            ['path' => 'community/*'],
        ],
    ],

    'warmup' => [
        'enabled' => env('PERFORMANCE_CACHE_WARM_ENABLED', env('APP_ENV') !== 'local'),
        'schedule' => env('PERFORMANCE_CACHE_WARM_SCHEDULE', '02:00'),
        'warmers' => [
            App\Support\Caching\Warmers\NavigationCacheWarmer::class,
            App\Support\Caching\Warmers\SettingsCacheWarmer::class,
        ],
    ],
];
