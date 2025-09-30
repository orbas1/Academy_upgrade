<?php

declare(strict_types=1);

namespace Tests\Unit\Messaging;

use App\Models\NotificationDeliveryMetric;
use App\Models\NotificationSuppression;
use App\Services\Messaging\NotificationDeliverabilityRecorder;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationDeliverabilityRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2025_10_15_000000_create_notification_deliverability_tables.php',
            '--realpath' => false,
        ])->run();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notification_provider_statuses');
        Schema::dropIfExists('notification_delivery_metrics');
        Schema::dropIfExists('notification_suppressions');

        parent::tearDown();
    }

    public function test_records_sent_and_failure_and_suppression(): void
    {
        $recorder = app(NotificationDeliverabilityRecorder::class);

        $recorder->recordSent('uuid-1', null, 'email', 'ses', 'post.created', ['foo' => 'bar']);
        $recorder->recordFailure('uuid-1', null, 'email', 'ses', 'post.created', ['error' => 'bounce']);
        $recorder->recordSuppression('email', 'user@example.com', 'bounced', 'ses', ['reason' => 'hard']);

        $this->assertDatabaseHas('notification_delivery_metrics', [
            'notification_id' => 'uuid-1',
            'user_id' => null,
            'channel' => 'email',
            'status' => 'sent',
            'provider' => 'ses',
        ]);

        $this->assertDatabaseHas('notification_delivery_metrics', [
            'notification_id' => 'uuid-1',
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('notification_suppressions', [
            'channel' => 'email',
            'identifier' => 'user@example.com',
            'reason' => 'bounced',
        ]);

        $suppression = NotificationSuppression::query()->first();
        $this->assertNotNull($suppression->payload);
        $this->assertSame('ses', $suppression->provider);

        $metrics = NotificationDeliveryMetric::query()->count();
        $this->assertSame(2, $metrics);
    }
}
