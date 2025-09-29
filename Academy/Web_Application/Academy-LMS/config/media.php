<?php

return [
    'default_disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),

    'cdn_url' => env('MEDIA_CDN_URL'),

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
