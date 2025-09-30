<?php

return [
    'default_locale' => env('APP_LOCALE', 'en'),

    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'regional' => 'en_US',
            'script' => 'Latn',
            'direction' => 'ltr',
        ],
        'es' => [
            'name' => 'Spanish',
            'native' => 'Español',
            'regional' => 'es_ES',
            'script' => 'Latn',
            'direction' => 'ltr',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'regional' => 'ar_SA',
            'script' => 'Arab',
            'direction' => 'rtl',
        ],
    ],

    'rtl_locales' => ['ar'],

    'cookie_name' => env('APP_LOCALE_COOKIE', 'app_locale'),

    'cookie_lifetime_minutes' => 60 * 24 * 365 * 2,

    'cookie_same_site' => 'lax',
];
