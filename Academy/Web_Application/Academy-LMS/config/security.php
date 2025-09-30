<?php

return [
    'uploads' => [
        'max_size' => env('UPLOAD_MAX_SIZE', 20 * 1024 * 1024),
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'application/pdf',
            'application/zip',
            'video/mp4',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'image_quality' => env('UPLOAD_IMAGE_QUALITY', 90),
        'block_until_clean' => env('UPLOAD_BLOCK_UNTIL_CLEAN', true),
        'queue' => env('UPLOAD_SECURITY_QUEUE', 'security'),
        'scanner' => [
            'enabled' => env('UPLOAD_SCANNER_ENABLED', true),
            'command' => env('UPLOAD_SCANNER_COMMAND', '/usr/bin/clamdscan'),
            'arguments' => explode(' ', env('UPLOAD_SCANNER_ARGUMENTS', '--no-summary')),
            'timeout' => env('UPLOAD_SCANNER_TIMEOUT', 30),
            'quarantine_path' => env('UPLOAD_QUARANTINE_PATH', storage_path('app/quarantine')),
        ],
    ],
    'device_trust' => [
        'ttl_days' => env('DEVICE_TRUST_TTL_DAYS', 60),
        'max_devices' => env('DEVICE_TRUST_MAX_DEVICES', 5),
    ],
    'sessions' => [
        'max_parallel_tokens' => env('SESSION_MAX_PARALLEL_TOKENS', 10),
    ],
];
