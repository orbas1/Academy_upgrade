<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

use Illuminate\Support\Arr;

final class CheckDefinition
{
    public function __construct(
        public readonly string $type,
        public readonly string $identifier,
        public readonly float $weight = 1.0,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $type = (string) Arr::get($payload, 'type');
        $identifier = (string) Arr::get($payload, 'identifier');
        $weight = (float) Arr::get($payload, 'weight', 1.0);
        $metadata = (array) Arr::get($payload, 'metadata', []);

        return new self($type, $identifier, $weight > 0 ? $weight : 1.0, $metadata);
    }
}
