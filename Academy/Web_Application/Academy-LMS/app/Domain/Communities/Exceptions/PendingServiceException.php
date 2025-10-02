<?php

namespace App\Domain\Communities\Exceptions;

use RuntimeException;
use function sprintf;

/**
 * Raised when a community domain contract does not yet have a concrete implementation.
 */
final class PendingServiceException extends RuntimeException
{
    public static function for(string $serviceClass, string $method): self
    {
        return new self(sprintf('%s::%s is pending implementation.', $serviceClass, $method));
    }
}

