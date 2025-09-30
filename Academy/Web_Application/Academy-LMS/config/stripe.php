<?php

return [
    'secret_key' => env('STRIPE_SECRET', null),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', null),
    'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),
];
