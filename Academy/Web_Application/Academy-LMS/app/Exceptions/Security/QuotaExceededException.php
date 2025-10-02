<?php

namespace App\Exceptions\Security;

use Throwable;

class QuotaExceededException extends UnsafeFileException
{
    public function __construct(string $message = 'Upload quota exceeded.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
