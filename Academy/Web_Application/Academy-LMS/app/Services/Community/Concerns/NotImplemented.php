<?php

declare(strict_types=1);

namespace App\Services\Community\Concerns;

use LogicException;

trait NotImplemented
{
    protected function notImplemented(): never
    {
        throw new LogicException('Community service implementation pending Section 2.2');
    }
}
