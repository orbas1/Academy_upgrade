<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket' => env('CLOUDFLARE_R2_BUCKET', env('AWS_BUCKET')),
            'url' => env('CLOUDFLARE_R2_URL', env('AWS_URL')),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'throw' => false,
        ],

        's3-backups' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_BACKUP_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'))),
            'secret' => env('CLOUDFLARE_R2_BACKUP_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'region' => env('CLOUDFLARE_R2_BACKUP_REGION', env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION'))),
            'bucket' => env('CLOUDFLARE_R2_BACKUP_BUCKET', env('CLOUDFLARE_R2_BUCKET', env('AWS_BUCKET'))),
            'url' => env('CLOUDFLARE_R2_BACKUP_URL', env('AWS_BACKUP_URL')),
            'endpoint' => env('CLOUDFLARE_R2_BACKUP_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT'))),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_BACKUP_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))),
            'throw' => false,
        ],

        'media' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'bucket' => env('CLOUDFLARE_R2_MEDIA_BUCKET', env('CLOUDFLARE_R2_BUCKET', env('AWS_BUCKET'))),
            'url' => env('MEDIA_CDN_URL', env('CLOUDFLARE_R2_URL', env('AWS_URL'))),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'visibility' => env('MEDIA_VISIBILITY', 'public'),
            'throw' => false,
            'options' => array_filter([
                'ServerSideEncryption' => env('CLOUDFLARE_R2_MEDIA_SSE', env('AWS_MEDIA_SSE', 'aws:kms')),
                'SSEKMSKeyId' => env('CLOUDFLARE_R2_MEDIA_KMS_KEY', env('AWS_MEDIA_KMS_KEY')),
            ]),
        ],

        'community-media' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'))),
            'secret' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'region' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_REGION', env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION'))),
            'bucket' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_BUCKET', env('AWS_COMMUNITY_MEDIA_BUCKET', 'academy-community-media')),
            'url' => env('COMMUNITY_MEDIA_CDN_URL', env('MEDIA_CDN_URL', env('CLOUDFLARE_R2_URL', env('AWS_URL')))),
            'endpoint' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT'))),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))),
            'visibility' => env('COMMUNITY_MEDIA_VISIBILITY', 'public'),
            'throw' => false,
            'options' => array_filter([
                'ServerSideEncryption' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_SSE', env('AWS_COMMUNITY_MEDIA_SSE', 'aws:kms')),
                'SSEKMSKeyId' => env('CLOUDFLARE_R2_COMMUNITY_MEDIA_KMS_KEY', env('AWS_COMMUNITY_MEDIA_KMS_KEY')),
            ]),
        ],

        'community-avatars' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'))),
            'secret' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'region' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_REGION', env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION'))),
            'bucket' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_BUCKET', env('AWS_COMMUNITY_AVATARS_BUCKET', 'academy-avatars')),
            'url' => env('COMMUNITY_AVATARS_CDN_URL', env('COMMUNITY_MEDIA_CDN_URL', env('CLOUDFLARE_R2_URL', env('AWS_URL')))),
            'endpoint' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT'))),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))),
            'visibility' => env('COMMUNITY_AVATARS_VISIBILITY', 'public'),
            'throw' => false,
            'options' => array_filter([
                'ServerSideEncryption' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_SSE', env('AWS_COMMUNITY_AVATARS_SSE', 'aws:kms')),
                'SSEKMSKeyId' => env('CLOUDFLARE_R2_COMMUNITY_AVATARS_KMS_KEY', env('AWS_COMMUNITY_AVATARS_KMS_KEY')),
            ]),
        ],

        'community-banners' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'))),
            'secret' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'region' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_REGION', env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION'))),
            'bucket' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_BUCKET', env('AWS_COMMUNITY_BANNERS_BUCKET', 'academy-banners')),
            'url' => env('COMMUNITY_BANNERS_CDN_URL', env('COMMUNITY_MEDIA_CDN_URL', env('CLOUDFLARE_R2_URL', env('AWS_URL')))),
            'endpoint' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT'))),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))),
            'visibility' => env('COMMUNITY_BANNERS_VISIBILITY', 'public'),
            'throw' => false,
            'options' => array_filter([
                'ServerSideEncryption' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_SSE', env('AWS_COMMUNITY_BANNERS_SSE', 'aws:kms')),
                'SSEKMSKeyId' => env('CLOUDFLARE_R2_COMMUNITY_BANNERS_KMS_KEY', env('AWS_COMMUNITY_BANNERS_KMS_KEY')),
            ]),
        ],

        'audit-logs' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_AUDIT_LOGS_ACCESS_KEY_ID', env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'))),
            'secret' => env('CLOUDFLARE_R2_AUDIT_LOGS_SECRET_ACCESS_KEY', env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'))),
            'region' => env('CLOUDFLARE_R2_AUDIT_LOGS_REGION', env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION'))),
            'bucket' => env('CLOUDFLARE_R2_AUDIT_LOGS_BUCKET', env('AWS_AUDIT_LOGS_BUCKET', 'academy-audit-logs')),
            'url' => env('CLOUDFLARE_R2_AUDIT_LOGS_URL', env('AWS_AUDIT_LOGS_URL')),
            'endpoint' => env('CLOUDFLARE_R2_AUDIT_LOGS_ENDPOINT', env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT'))),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_AUDIT_LOGS_USE_PATH_STYLE_ENDPOINT', env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false))),
            'visibility' => 'private',
            'throw' => false,
            'options' => array_filter([
                'ServerSideEncryption' => env('CLOUDFLARE_R2_AUDIT_LOGS_SSE', env('AWS_AUDIT_LOGS_SSE', 'aws:kms')),
                'SSEKMSKeyId' => env('CLOUDFLARE_R2_AUDIT_LOGS_KMS_KEY', env('AWS_AUDIT_LOGS_KMS_KEY')),
                'ObjectLockMode' => env('CLOUDFLARE_R2_AUDIT_LOGS_OBJECT_LOCK_MODE', env('AWS_AUDIT_LOGS_OBJECT_LOCK_MODE', 'COMPLIANCE')),
                'ObjectLockLegalHoldStatus' => env('CLOUDFLARE_R2_AUDIT_LOGS_LEGAL_HOLD', env('AWS_AUDIT_LOGS_LEGAL_HOLD')),
            ], static fn ($value) => $value !== null && $value !== ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
