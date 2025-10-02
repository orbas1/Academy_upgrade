<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

final class CheckResult
{
    public function __construct(
        public readonly CheckDefinition $definition,
        public readonly bool $passed,
        public readonly ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->definition->type,
            'identifier' => $this->definition->identifier,
            'weight' => $this->definition->weight,
            'metadata' => $this->definition->metadata,
            'passed' => $this->passed,
            'message' => $this->message,
        ];
    }
}
