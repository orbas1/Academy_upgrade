<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),
    'hash_key' => env('ANALYTICS_HASH_KEY', env('APP_KEY')),
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 395),
    'segment' => [
        'write_key' => env('SEGMENT_WRITE_KEY'),
        'endpoint' => env('SEGMENT_ENDPOINT', 'https://api.segment.io/v1/track'),
        'timeout' => (float) env('SEGMENT_TIMEOUT', 2.0),
    ],
    'consent' => [
        'required' => env('ANALYTICS_REQUIRE_CONSENT', true),
        'version' => env('ANALYTICS_CONSENT_VERSION', '2025-01'),
    ],
];
