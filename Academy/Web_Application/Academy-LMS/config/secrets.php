<?php

return [
    'default' => env('SECRET_MANAGER_DRIVER', env('SECRETS_MANAGER_DRIVER', 'env')),

    'cache_ttl' =>(int) env('SECRET_CACHE_TTL', 300),

    'sync' => [
        'keys' => array_filter(array_map('trim', explode(',', env('SECRET_SYNC_KEYS', '')))),
    ],

    'rotation' => [
        'enabled' => env('SECRET_ROTATION_ENABLED', false),
        'cron' => env('SECRET_ROTATION_CRON', '0 3 * * *'),
        'keys' => array_filter(array_map('trim', explode(',', env('SECRET_ROTATION_KEYS', '')))),
        'driver' => env('SECRET_ROTATION_DRIVER', null),
    ],

    'drivers' => [
        'env' => [
            'class' => App\Support\Secrets\Drivers\EnvSecretDriver::class,
            'prefix' => env('SECRET_ENV_PREFIX', ''),
        ],
        'aws' => [
            'class' => App\Support\Secrets\Drivers\AwsSecretsManagerDriver::class,
            'region' => env('CLOUDFLARE_R2_SECRET_MANAGER_REGION', env('AWS_SECRET_MANAGER_REGION', env('AWS_DEFAULT_REGION', 'us-east-1'))),
        ],
        'vault' => [
            'class' => App\Support\Secrets\Drivers\HashicorpVaultDriver::class,
            'base_uri' => env('VAULT_ADDR', null),
            'token' => env('VAULT_TOKEN', null),
            'mount' => env('VAULT_KV_MOUNT', 'secret'),
            'namespace' => env('VAULT_NAMESPACE', null),
        ],
    ],
];
