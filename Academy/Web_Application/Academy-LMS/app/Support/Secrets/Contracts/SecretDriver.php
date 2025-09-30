<?php

namespace App\Support\Secrets\Contracts;

use App\Support\Secrets\SecretValue;
use App\Support\Secrets\SecretRotationResult;

interface SecretDriver
{
    /**
     * Retrieve a secret from the underlying provider.
     *
     * @param  array<string, mixed>  $options
     */
    public function get(string $key, array $options = []): ?SecretValue;

    /**
     * Persist or update a secret in the underlying provider.
     *
     * @param  array<string, mixed>  $options
     */
    public function put(string $key, string $value, array $options = []): SecretRotationResult;

    /**
     * Trigger rotation for the given secret and return metadata for the new version.
     *
     * @param  array<string, mixed>  $options
     */
    public function rotate(string $key, array $options = []): SecretRotationResult;
}
