<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Lifecycle Profiles
    |--------------------------------------------------------------------------
    | Each profile describes how a storage bucket should behave through its
    | lifecycle. Buckets may have different transition and expiration policies
    | depending on the content type handled by the platform. These defaults can
    | be customised via environment variables so that infrastructure teams can
    | adapt policies per environment without touching code.
    */
    'profiles' => [
        'media' => [
            'bucket' => env('CLOUDFLARE_R2_BUCKET', env('AWS_BUCKET', null)),
            'prefix' => env('STORAGE_MEDIA_PREFIX', 'media/'),
            'transitions' => [
                [
                    'storage_class' => 'STANDARD_IA',
                    'days' => env('STORAGE_MEDIA_STANDARD_IA_DAYS', 30),
                ],
                [
                    'storage_class' => 'GLACIER',
                    'days' => env('STORAGE_MEDIA_GLACIER_DAYS', 180),
                ],
            ],
            'expiration_days' => env('STORAGE_MEDIA_EXPIRATION_DAYS', 730),
            'abort_multipart_days' => env('STORAGE_MEDIA_ABORT_DAYS', 7),
            'noncurrent_versions' => [
                'transition' => [
                    'storage_class' => 'GLACIER',
                    'days' => env('STORAGE_MEDIA_NONCURRENT_GLACIER_DAYS', 30),
                ],
                'expiration_days' => env('STORAGE_MEDIA_NONCURRENT_EXPIRATION_DAYS', 365),
            ],
            'backup' => [
                'enabled' => env('STORAGE_MEDIA_BACKUP_ENABLED', true),
                'cron' => env('STORAGE_MEDIA_BACKUP_CRON', '0 3 * * *'),
                'retention_days' => env('STORAGE_MEDIA_BACKUP_RETENTION_DAYS', 30),
                'disk' => env('STORAGE_MEDIA_BACKUP_DISK', 's3-backups'),
                'database' => [
                    'enabled' => env('STORAGE_DATABASE_BACKUP_ENABLED', true),
                    'connection' => env('DB_CONNECTION', 'mysql'),
                ],
                'paths' => [
                    storage_path('app/public/uploads'),
                    storage_path('app/public/attachments'),
                ],
                'encryption' => [
                    'enabled' => env('STORAGE_BACKUP_ENCRYPTION_ENABLED', true),
                    'key' => env('STORAGE_BACKUP_ENCRYPTION_KEY'),
                    'cipher' => env('STORAGE_BACKUP_ENCRYPTION_CIPHER', 'aes-256-cbc'),
                ],
            ],
        ],

        'avatars' => [
            'bucket' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_BUCKET', env('AWS_COMMUNITY_AVATARS_BUCKET', 'academy-avatars')),
            'prefix' => env('STORAGE_AVATAR_PREFIX', 'avatars/'),
            'transitions' => [
                [
                    'storage_class' => 'STANDARD_IA',
                    'days' => env('STORAGE_AVATAR_STANDARD_IA_DAYS', 60),
                ],
            ],
            'expiration_days' => env('STORAGE_AVATAR_EXPIRATION_DAYS', 365),
            'abort_multipart_days' => env('STORAGE_AVATAR_ABORT_DAYS', 3),
            'kms_key' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_KMS_KEY', env('AWS_COMMUNITY_AVATARS_KMS_KEY', null)),
        ],

        'banners' => [
            'bucket' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_BUCKET', env('AWS_COMMUNITY_BANNERS_BUCKET', 'academy-banners')),
            'prefix' => env('STORAGE_BANNER_PREFIX', 'banners/'),
            'transitions' => [
                [
                    'storage_class' => 'STANDARD_IA',
                    'days' => env('STORAGE_BANNER_STANDARD_IA_DAYS', 30),
                ],
                [
                    'storage_class' => 'GLACIER',
                    'days' => env('STORAGE_BANNER_GLACIER_DAYS', 365),
                ],
            ],
            'expiration_days' => env('STORAGE_BANNER_EXPIRATION_DAYS', 1095),
            'abort_multipart_days' => env('STORAGE_BANNER_ABORT_DAYS', 7),
            'kms_key' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_KMS_KEY', env('AWS_COMMUNITY_BANNERS_KMS_KEY', null)),
        ],

        'audit_logs' => [
            'bucket' => env('CLOUDFLARE_R2_AUDIT_LOGS_BUCKET', env('AWS_AUDIT_LOGS_BUCKET', 'academy-audit-logs')),
            'prefix' => env('STORAGE_AUDIT_PREFIX', 'audit/'),
            'transitions' => [
                [
                    'storage_class' => 'GLACIER',
                    'days' => env('STORAGE_AUDIT_GLACIER_DAYS', 90),
                ],
            ],
            'expiration_days' => env('STORAGE_AUDIT_EXPIRATION_DAYS', 3650),
            'abort_multipart_days' => env('STORAGE_AUDIT_ABORT_DAYS', 1),
            'kms_key' => env('CLOUDFLARE_R2_AUDIT_LOGS_KMS_KEY', env('AWS_AUDIT_LOGS_KMS_KEY', null)),
            'object_lock' => [
                'enabled' => env('STORAGE_AUDIT_OBJECT_LOCK_ENABLED', true),
                'mode' => env('STORAGE_AUDIT_OBJECT_LOCK_MODE', 'COMPLIANCE'),
                'retain_until_days' => env('STORAGE_AUDIT_OBJECT_LOCK_RETAIN_DAYS', 3650),
            ],
        ],
    ],
];
