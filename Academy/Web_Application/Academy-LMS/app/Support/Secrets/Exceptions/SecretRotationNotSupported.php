<?php

namespace App\Support\Secrets\Exceptions;

use RuntimeException;

class SecretRotationNotSupported extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf('Secret rotation is not supported by driver "%s".', $driver));
    }
}
