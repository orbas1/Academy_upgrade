<?php

return [
    'default' => 'en',

    'supported' => [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'legacy_language_key' => 'english',
            'aliases' => ['en-US', 'en_GB', 'english'],
        ],
        'es' => [
            'name' => 'Spanish',
            'native_name' => 'Español',
            'direction' => 'ltr',
            'legacy_language_key' => 'spanish',
            'aliases' => ['es-ES', 'es-MX', 'spanish'],
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'legacy_language_key' => 'arabic',
            'aliases' => ['ar-SA', 'ar', 'arabic'],
        ],
    ],

    'cookie' => [
        'name' => 'academy_locale',
        'lifetime_days' => 365,
        'secure' => env('SESSION_SECURE_COOKIE', false),
    ],

    'query_parameter' => 'lang',
];
