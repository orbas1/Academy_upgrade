<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_requires_token(): void
    {
        config([
            'cache.default' => 'array',
            'observability.prometheus.store' => 'array',
            'observability.prometheus.auth_token' => 'secret',
        ]);

        $registry = $this->app->make(PrometheusRegistry::class);
        $registry->incrementCounter('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ]);

        $this->getJson('/api/internal/metrics')->assertForbidden();

        $response = $this->withHeaders(['X-Observability-Token' => 'secret'])
            ->get('/api/internal/metrics');

        $response->assertOk();
        $response->assertSee('http_requests_total', false);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4');
    }
}
