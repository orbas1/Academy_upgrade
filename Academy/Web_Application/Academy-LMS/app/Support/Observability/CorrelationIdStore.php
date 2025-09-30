<?php

declare(strict_types=1);

namespace App\Support\Observability;

class CorrelationIdStore
{
    private ?string $correlationId = null;

    public function set(string $identifier): void
    {
        $this->correlationId = $identifier;
    }

    public function get(): ?string
    {
        return $this->correlationId;
    }

    public function clear(): void
    {
        $this->correlationId = null;
    }
}
