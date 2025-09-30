<?php

declare(strict_types=1);

namespace App\Support\Observability\Metrics;

interface MetricTransport
{
    public function send(string $payload): void;
}
