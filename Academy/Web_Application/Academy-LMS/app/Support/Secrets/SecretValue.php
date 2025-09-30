<?php

namespace App\Support\Secrets;

use Carbon\CarbonImmutable;

class SecretValue
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
        public readonly string $version,
        public readonly CarbonImmutable $retrievedAt,
        public readonly ?CarbonImmutable $rotatedAt = null,
        public readonly array $metadata = [],
    ) {
    }

    public function maskedValue(int $visible = 4): string
    {
        $length = mb_strlen($this->value);
        $visible = max(0, min($visible, $length));

        return str_repeat('•', max(0, $length - $visible)) . mb_substr($this->value, $length - $visible);
    }
}
