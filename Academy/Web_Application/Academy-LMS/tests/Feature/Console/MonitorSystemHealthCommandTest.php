<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Notifications\SystemHealthAlert;
use App\Support\Observability\Metrics\PrometheusRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MonitorSystemHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_emits_alert_on_high_error_rate(): void
    {
        config([
            'cache.default' => 'array',
            'observability.prometheus.store' => 'array',
            'observability.alerts' => [
                'enabled' => true,
                'metric_window_seconds' => 300,
                'error_rate_threshold' => 0.1,
                'notification_channels' => ['mail' => 'ops@example.com'],
                'queues' => [],
                'queue_lag_threshold_seconds' => 9999,
                'queue_backlog_threshold' => 9999,
                'disk_free_ratio_threshold' => 0,
                'redis_connection' => null,
                'cooldown_seconds' => 0,
            ],
        ]);

        $registry = $this->app->make(PrometheusRegistry::class);
        $registry->incrementCounter('http_requests_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], 10);
        $registry->incrementCounter('http_requests_errors_total', [
            'route' => '__all__',
            'method' => '__all__',
            'status_class' => 'all',
        ], 5);

        Notification::fake();

        $this->artisan('observability:monitor-health')->assertExitCode(0);

        Notification::assertSentOnDemand(SystemHealthAlert::class, function (SystemHealthAlert $notification, array $channels): bool {
            return in_array('mail', $channels, true);
        });
    }
}
