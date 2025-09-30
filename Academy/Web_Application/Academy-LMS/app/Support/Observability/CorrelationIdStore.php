<?php

declare(strict_types=1);

namespace App\Support\Observability;

class CorrelationIdStore
{
    private ?string $correlationId = null;

    /**
     * @var callable|null
     */
    private $onUpdated;

    public function __construct(?callable $onUpdated = null)
    {
        $this->onUpdated = $onUpdated;
    }

    public function set(string $identifier): void
    {
        $this->correlationId = $identifier;
        $this->triggerUpdate();
    }

    public function get(): ?string
    {
        return $this->correlationId;
    }

    public function clear(): void
    {
        $this->correlationId = null;
        $this->triggerUpdate();
    }

    private function triggerUpdate(): void
    {
        if ($this->onUpdated !== null) {
            ($this->onUpdated)($this->correlationId);
        }
    }
}
