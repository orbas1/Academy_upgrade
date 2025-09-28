<?php

namespace App\Domain\Shared;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Base immutable DTO used across domain boundaries.
 * Provides array/json casting and guards against unknown properties.
 */
abstract class DataTransferObject implements Arrayable, JsonSerializable
{
    /**
     * DTOs are treated as immutable value objects.
     */
    final public function __set(string $name, mixed $value): void
    {
        throw new \LogicException(sprintf('Cannot set undefined property %s on immutable DTO %s', $name, static::class));
    }

    final public function __get(string $name): mixed
    {
        throw new \LogicException(sprintf('Property %s does not exist on DTO %s', $name, static::class));
    }

    public function toArray(): array
    {
        return $this->normalize($this);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (is_iterable($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }

            return $normalized;
        }

        return $value;
    }
}
