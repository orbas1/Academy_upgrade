<?php

return [
    'default_disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),

    'cdn_url' => env('MEDIA_CDN_URL', null),

    'signed_urls' => [
        'enabled' => (bool) env('MEDIA_SIGNED_URLS', false),
        'ttl_minutes' => (int) env('MEDIA_SIGNED_URL_TTL', 10),
        'response_headers' => array_filter([
            'ResponseContentDisposition' => env('MEDIA_SIGNED_URL_DISPOSITION'),
            'ResponseContentType' => env('MEDIA_SIGNED_URL_CONTENT_TYPE'),
        ], static fn ($value) => $value !== null && $value !== ''),
    ],

    'image_breakpoints' => array_values(array_filter(array_map(
        static function (string $value): int {
            return (int) trim($value);
        },
        explode(',', env('MEDIA_IMAGE_BREAKPOINTS', '320,640,960,1280'))
    ))),

    'queue' => env('MEDIA_TRANSCODE_QUEUE', 'media'),

    'visibility' => env('MEDIA_VISIBILITY', 'public'),

    'responsive_prefix' => env('MEDIA_RESPONSIVE_PREFIX', 'responsive'),

    'transcoded_prefix' => env('MEDIA_TRANSCODE_PREFIX', 'transcoded'),
];
