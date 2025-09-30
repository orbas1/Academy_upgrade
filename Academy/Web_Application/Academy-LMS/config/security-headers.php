<?php

use App\Support\Security\SecurityHeaderValueBuilder as Headers;

$scriptSources = Headers::mergeSources([
    "'self'",
    "'unsafe-inline'",
    "'unsafe-eval'",
    'cdn.*',
], env('SECURITY_HEADERS_SCRIPT_SRC', ''));

$styleSources = Headers::mergeSources([
    "'self'",
    "'unsafe-inline'",
    'fonts.googleapis.com',
], env('SECURITY_HEADERS_STYLE_SRC', ''));

$fontSources = Headers::mergeSources([
    "'self'",
    'fonts.gstatic.com',
], env('SECURITY_HEADERS_FONT_SRC', ''));

$imgSources = Headers::mergeSources([
    "'self'",
    'data:',
    'https:',
], env('SECURITY_HEADERS_IMG_SRC', ''));

$mediaSources = Headers::mergeSources([
    'https:',
], env('SECURITY_HEADERS_MEDIA_SRC', ''));

$connectSources = Headers::mergeSources([
    "'self'",
    'https:',
    'wss:',
], env('SECURITY_HEADERS_CONNECT_SRC', ''));

$mobileConnectSources = Headers::mergeSources($connectSources, env('SECURITY_HEADERS_MOBILE_CONNECT_SRC', ''));

$mobileImageSources = Headers::mergeSources($imgSources, ['blob:']);
$mobileMediaSources = Headers::mergeSources($mediaSources, ['blob:']);

$defaultPermissions = Headers::permissionsPolicy([
    'geolocation' => ['self'],
    'camera' => null,
    'microphone' => null,
]);

$mobilePermissions = Headers::permissionsPolicy([
    'geolocation' => Headers::mergeSources(['self'], env('SECURITY_HEADERS_MOBILE_GEO_ORIGINS', 'academyapp://callback https://academy.local')),
    'camera' => null,
    'microphone' => null,
]);

$defaultCsp = Headers::contentSecurityPolicy([
    'default-src' => ["'self'"],
    'img-src' => $imgSources,
    'media-src' => $mediaSources,
    'script-src' => $scriptSources,
    'style-src' => $styleSources,
    'font-src' => $fontSources,
    'connect-src' => $connectSources,
    'frame-ancestors' => ["'none'"],
    'base-uri' => ["'self'"],
]);

$apiCsp = Headers::contentSecurityPolicy([
    'default-src' => ["'none'"],
    'img-src' => Headers::mergeSources([
        "'self'",
        'data:',
    ], env('SECURITY_HEADERS_API_IMG_SRC', '')),
    'script-src' => ["'none'"],
    'style-src' => ["'none'"],
    'font-src' => ["'none'"],
    'media-src' => ["'none'"],
    'connect-src' => $connectSources,
    'frame-ancestors' => ["'none'"],
    'base-uri' => ["'none'"],
]);

$mobileApiCsp = Headers::contentSecurityPolicy([
    'default-src' => ["'none'"],
    'img-src' => $mobileImageSources,
    'media-src' => $mobileMediaSources,
    'script-src' => ["'none'"],
    'style-src' => ["'none'"],
    'font-src' => ["'none'"],
    'connect-src' => $mobileConnectSources,
    'frame-ancestors' => ["'none'"],
    'base-uri' => ["'none'"],
]);

return [
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    'exclusions' => [
        'paths' => [
            // 'status/health',
        ],
        'methods' => [
            // 'OPTIONS',
        ],
    ],

    'headers' => [
        'Strict-Transport-Security' => Headers::strictTransportSecurity(63072000, true, true),
        'Content-Security-Policy' => $defaultCsp,
        'Referrer-Policy' => 'same-origin',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Resource-Policy' => 'same-site',
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Permissions-Policy' => $defaultPermissions,
    ],

    'profiles' => [
        'web-app' => [],
        'api' => [
            'Content-Security-Policy' => $apiCsp,
            'Referrer-Policy' => 'no-referrer',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Permissions-Policy' => Headers::permissionsPolicy([
                'geolocation' => null,
                'camera' => null,
                'microphone' => null,
            ]),
        ],
        'mobile-api' => [
            'Content-Security-Policy' => $mobileApiCsp,
            'Referrer-Policy' => 'no-referrer',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Permissions-Policy' => $mobilePermissions,
        ],
    ],

    'auto_profiles' => [
        'headers' => [
            'X-Academy-Client' => [
                'mobile-app*' => 'mobile-api',
                'web-app*' => 'api',
            ],
        ],
        'paths' => [
            'api/*' => 'api',
            'broadcasting/auth' => 'api',
        ],
        'accepts' => [
            'application/json' => 'api',
            'application/vnd.academy.v1+json' => 'api',
        ],
        'ajax' => 'api',
        'expects_json' => 'api',
    ],
];
