<?php

return [
    'export_disk' => env('COMPLIANCE_EXPORT_DISK', 'local'),

    'redacted_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'two_factor_code',
        'recovery_code',
    ],

    'max_payload_length' => env('COMPLIANCE_MAX_PAYLOAD', 2000),
];
