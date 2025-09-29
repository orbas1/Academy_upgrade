<?php

return [
    'secret_key' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),
];
