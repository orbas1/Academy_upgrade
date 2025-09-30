<?php

namespace App\Support\Secrets\Drivers;

use App\Support\Secrets\Contracts\SecretDriver;
use App\Support\Secrets\Exceptions\SecretRotationNotSupported;
use App\Support\Secrets\SecretRotationResult;
use App\Support\Secrets\SecretValue;
use Carbon\CarbonImmutable;

class EnvSecretDriver implements SecretDriver
{
    public function __construct(private readonly ?string $prefix = null)
    {
    }

    public function get(string $key, array $options = []): ?SecretValue
    {
        $envKey = $this->applyPrefix($key);
        $value = env($envKey);

        if ($value === null) {
            return null;
        }

        return new SecretValue(
            key: $key,
            value: (string) $value,
            version: 'env',
            retrievedAt: CarbonImmutable::now(),
            metadata: ['source' => 'env', 'env_key' => $envKey],
        );
    }

    public function put(string $key, string $value, array $options = []): SecretRotationResult
    {
        throw SecretRotationNotSupported::forDriver('env');
    }

    public function rotate(string $key, array $options = []): SecretRotationResult
    {
        throw SecretRotationNotSupported::forDriver('env');
    }

    private function applyPrefix(string $key): string
    {
        if (! $this->prefix) {
            return $key;
        }

        return rtrim($this->prefix, '_') . '_' . ltrim($key, '_');
    }
}
