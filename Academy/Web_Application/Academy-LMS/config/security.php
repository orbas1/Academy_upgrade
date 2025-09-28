<?php

return [
    'admin_ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))))),
    'two_factor' => [
        'issuer' => env('TWO_FACTOR_ISSUER', env('APP_NAME', 'Academy')),
        'window' => (int) env('TWO_FACTOR_WINDOW', 1),
        'remember_device_ttl' => (int) env('TWO_FACTOR_REMEMBER_DAYS', 30),
    ],
];
