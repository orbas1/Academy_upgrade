<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

use Illuminate\Support\Arr;

final class RequirementDefinition
{
    /**
     * @param  array<int, CheckDefinition>  $checks
     * @param  array<int, array<string, string>>  $evidence
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly array $checks,
        public readonly array $evidence,
        public readonly array $tags,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $checks = collect(Arr::get($payload, 'checks', []))
            ->map(fn (array $attributes) => CheckDefinition::fromArray($attributes))
            ->values()
            ->all();

        $evidence = collect(Arr::get($payload, 'evidence', []))
            ->map(function ($item) {
                return [
                    'type' => (string) Arr::get($item, 'type', 'unspecified'),
                    'identifier' => (string) Arr::get($item, 'identifier'),
                ];
            })
            ->values()
            ->all();

        $tags = collect(Arr::get($payload, 'tags', []))
            ->map(fn ($tag) => (string) $tag)
            ->filter()
            ->values()
            ->all();

        return new self(
            (string) Arr::get($payload, 'id'),
            (string) Arr::get($payload, 'title'),
            (string) Arr::get($payload, 'description'),
            $checks,
            $evidence,
            $tags,
        );
    }
}
