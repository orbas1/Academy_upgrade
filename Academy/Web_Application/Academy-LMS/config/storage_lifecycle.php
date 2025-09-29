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
            'bucket' => env('AWS_BUCKET'),
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
            ],
        ],
    ],
];
