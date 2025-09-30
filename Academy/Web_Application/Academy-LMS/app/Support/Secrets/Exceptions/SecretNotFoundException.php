<?php

namespace App\Support\Secrets\Exceptions;

use RuntimeException;

class SecretNotFoundException extends RuntimeException
{
    public static function forKey(string $key, string $driver): self
    {
        return new self(sprintf('Secret "%s" was not found using driver "%s".', $key, $driver));
    }
}
