<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability\Metrics;

use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PrometheusRegistryTest extends TestCase
{
    public function test_it_renders_metrics(): void
    {
        config(['cache.default' => 'array']);

        $registry = new PrometheusRegistry(
            Cache::store('array'),
            [
                'prefix' => 'test',
                'default_buckets' => [0.1, 1.0],
                'lock_seconds' => 1,
                'lock_wait_seconds' => 1,
            ]
        );

        $registry->incrementCounter('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], 2.0);

        $registry->observeHistogram('http_request_duration_seconds', 0.05, [
            'route' => 'community.index',
            'method' => 'get',
            'status_class' => '2xx',
        ]);

        $registry->setGauge('queue_lag_seconds', 12.5, [
            'job' => '__all__',
            'connection' => 'redis',
            'queue' => 'default',
        ]);

        $metrics = $registry->render();

        $this->assertStringContainsString('http_requests_total{method="__all__",route="__all__",status_class="all"} 2', $metrics);
        $this->assertStringContainsString('http_request_duration_seconds_bucket{method="get",route="community.index",status_class="2xx",le="0.1"} 1', $metrics);
        $this->assertStringContainsString('queue_lag_seconds{connection="redis",job="__all__",queue="default"} 12.5', $metrics);
    }

    public function test_it_calculates_window_sums(): void
    {
        config(['cache.default' => 'array']);

        $registry = new PrometheusRegistry(
            Cache::store('array'),
            [
                'prefix' => 'window-test',
                'default_buckets' => [0.1],
                'lock_seconds' => 1,
                'lock_wait_seconds' => 1,
            ]
        );

        $registry->incrementCounter('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], 3.0);

        $sum = $registry->counterWindowSum('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], 300);

        $this->assertEquals(3.0, $sum);
    }
}
