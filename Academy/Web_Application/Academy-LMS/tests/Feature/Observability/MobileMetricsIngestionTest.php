<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\User;
use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileMetricsIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_metrics_are_ingested(): void
    {
        config([
            'cache.default' => 'array',
            'observability.prometheus.store' => 'array',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'session_id' => 'session-1',
            'metrics' => [
                [
                    'name' => 'http_request',
                    'timestamp' => now()->toIso8601String(),
                    'method' => 'GET',
                    'route' => 'communities.index',
                    'duration_ms' => 125.3,
                    'status_code' => 200,
                ],
            ],
        ];

        $response = $this->postJson('/api/observability/mobile-metrics', $payload);
        $response->assertOk();
        $response->assertJson(['processed' => 1]);

        $registry = $this->app->make(PrometheusRegistry::class);
        $sum = $registry->counterWindowSum('mobile_http_requests_total', [
            'route' => 'communities.index',
            'method' => 'get',
            'status_class' => '2xx',
        ], 600);

        $this->assertGreaterThan(0.0, $sum);
    }
}
