<?php

namespace App\Support\Secrets;

use Carbon\CarbonImmutable;

class SecretRotationResult
{
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly CarbonImmutable $rotatedAt,
        public readonly array $metadata = [],
    ) {
    }
}
