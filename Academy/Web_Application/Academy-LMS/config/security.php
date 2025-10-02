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
        'quota' => [
            'user' => [
                'limit_mb' => (int) env('UPLOAD_USER_QUOTA_MB', 2048),
                'window_days' => (int) env('UPLOAD_USER_QUOTA_WINDOW_DAYS', 30),
            ],
            'community' => [
                'limit_mb' => (int) env('UPLOAD_COMMUNITY_QUOTA_MB', 10240),
                'window_days' => (int) env('UPLOAD_COMMUNITY_QUOTA_WINDOW_DAYS', 30),
            ],
        ],
    ],
    'device_trust' => [
        'ttl_days' => env('DEVICE_TRUST_TTL_DAYS', 60),
        'max_devices' => env('DEVICE_TRUST_MAX_DEVICES', 5),
    ],
    'sessions' => [
        'max_parallel_tokens' => env('SESSION_MAX_PARALLEL_TOKENS', 10),
    ],

    'data_protection' => [
        'prune_schedule' => env('DATA_PROTECTION_PRUNE_SCHEDULE', '03:15'),
        'audit_logs' => [
            'retention_days' => (int) env('DATA_PROTECTION_AUDIT_LOG_RETENTION_DAYS', 3650),
        ],
        'device_sessions' => [
            'retention_days' => (int) env('DATA_PROTECTION_DEVICE_SESSION_RETENTION_DAYS', 180),
        ],
        'personal_access_tokens' => [
            'retention_days' => (int) env('DATA_PROTECTION_TOKEN_RETENTION_DAYS', 90),
        ],
        'backup' => [
            'enabled' => (bool) env('DATA_PROTECTION_BACKUP_ENABLED', false),
            'profile' => env('DATA_PROTECTION_BACKUP_PROFILE', 'media'),
        ],
        'upload_scans' => [
            'retention_days' => (int) env('DATA_PROTECTION_UPLOAD_SCAN_RETENTION_DAYS', 60),
            'quarantine_retention_days' => (int) env('DATA_PROTECTION_UPLOAD_QUARANTINE_RETENTION_DAYS', 30),
        ],
        'upload_usage' => [
            'retention_days' => (int) env('DATA_PROTECTION_UPLOAD_USAGE_RETENTION_DAYS', 365),
        ],
        'exports' => [
            'disk' => env('DATA_PROTECTION_EXPORT_DISK', env('COMPLIANCE_EXPORT_DISK', 'local')),
            'path' => env('DATA_PROTECTION_EXPORT_PATH', 'compliance/exports'),
            'retention_days' => (int) env('DATA_PROTECTION_EXPORT_RETENTION_DAYS', 30),
        ],
    ],
];
