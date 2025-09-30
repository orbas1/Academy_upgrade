<?php

declare(strict_types=1);

namespace Tests\Unit\Messaging;

use App\Services\Messaging\NotificationProviderHealthService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationProviderHealthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2025_10_15_000000_create_notification_deliverability_tables.php',
            '--realpath' => false,
        ])->run();

        config()->set('messaging.email.providers', [
            'ses' => [
                'mailer' => 'ses',
                'priority' => 10,
                'failures_to_trip' => 2,
                'cooldown_seconds' => 60,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notification_provider_statuses');
        Schema::dropIfExists('notification_delivery_metrics');
        Schema::dropIfExists('notification_suppressions');

        parent::tearDown();
    }

    public function test_provider_is_healthy_by_default(): void
    {
        $service = app(NotificationProviderHealthService::class);

        $this->assertTrue($service->isHealthy('email', 'ses'));
    }

    public function test_provider_trips_after_threshold(): void
    {
        $service = app(NotificationProviderHealthService::class);

        $service->markFailure('email', 'ses', 'timeout');
        $this->assertTrue($service->isHealthy('email', 'ses'));

        $service->markFailure('email', 'ses', 'timeout');

        $this->assertFalse($service->isHealthy('email', 'ses'));
    }

    public function test_provider_recovers_after_cooldown(): void
    {
        $service = app(NotificationProviderHealthService::class);

        $service->markFailure('email', 'ses', 'timeout');
        $service->markFailure('email', 'ses', 'timeout');

        $this->assertFalse($service->isHealthy('email', 'ses'));

        $this->travel(70)->seconds();

        $this->assertTrue($service->isHealthy('email', 'ses'));
    }
}
