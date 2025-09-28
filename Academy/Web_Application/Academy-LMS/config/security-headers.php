<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Toggle
    |--------------------------------------------------------------------------
    |
    | This flag allows operations teams to disable the middleware quickly if a
    | misconfiguration is detected during rollout. It should remain enabled in
    | all environments once verification has passed.
    |
    */
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    |
    | Define request path patterns or HTTP verbs that should bypass automatic
    | header injection. This is useful for health checks or third-party call
    | backs where upstream providers enforce their own policies.
    |
    */
    'exclusions' => [
        'paths' => [
            // 'status/health',
        ],
        'methods' => [
            // 'OPTIONS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | These headers are applied to every response unless a specific profile is
    | provided. Values align with the platform upgrade blueprint and can be
    | overridden per environment via config caching.
    |
    */
    'headers' => [
        'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
        'Content-Security-Policy'   => "default-src 'self'; img-src 'self' data: https:; media-src https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.*; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; connect-src 'self' https: wss:; frame-ancestors 'none'; base-uri 'self'",
        'Referrer-Policy'           => 'same-origin',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Resource-Policy' => 'same-site',
        'X-Frame-Options'           => 'DENY',
        'X-Content-Type-Options'    => 'nosniff',
        'Permissions-Policy'        => 'geolocation=(self), camera=(), microphone=()'
    ],

    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    |
    | Profiles extend or override the defaults for contexts such as embedded
    | payment flows. Apply them with the `security.headers:{profile}` middleware
    | alias on routes that require tailored policies.
    |
    */
    'profiles' => [
        // 'stripe-checkout' => [
        //     'Content-Security-Policy' => "default-src 'self' https://js.stripe.com; frame-ancestors 'self' https://js.stripe.com; frame-src 'self' https://js.stripe.com; script-src 'self' https://js.stripe.com 'unsafe-inline'; connect-src 'self' https://api.stripe.com https://js.stripe.com",
        //     'X-Frame-Options'         => null,
        // ],
    ],
];
