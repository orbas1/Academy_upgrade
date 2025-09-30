<?php

declare(strict_types=1);

namespace App\Http\Controllers\Monitoring;

use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Http\Response;

class MetricsController
{
    public function __construct(private readonly PrometheusRegistry $registry)
    {
    }

    public function __invoke(): Response
    {
        $metrics = $this->registry->render();

        if ($metrics === '') {
            return response('', Response::HTTP_NO_CONTENT);
        }

        return response($metrics, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4',
        ]);
    }
}
