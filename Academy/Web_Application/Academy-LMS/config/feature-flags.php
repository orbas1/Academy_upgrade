<?php

$defaults = [
    'communities' => false,
    'webauthn' => false,
    'community_profile_activity' => false,
    'community_membership_beta' => false,
];

$fileOverrides = [];
$filePath = storage_path('app/feature-flags.json');
if (file_exists($filePath)) {
    $decoded = json_decode(file_get_contents($filePath), true);
    if (is_array($decoded)) {
        $fileOverrides = $decoded;
    }
}

$envOverrides = [];
$envJson = env('APP_FEATURE_FLAGS', '');
if ($envJson) {
    $decoded = json_decode($envJson, true);
    if (is_array($decoded)) {
        $envOverrides = $decoded;
    }
}

return array_merge($defaults, $fileOverrides, $envOverrides);
