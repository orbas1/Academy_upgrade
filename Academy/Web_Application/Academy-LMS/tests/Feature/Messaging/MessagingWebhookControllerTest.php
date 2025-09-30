<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Http\Controllers\Webhooks\MessagingWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessagingWebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2025_10_15_000000_create_notification_deliverability_tables.php',
            '--realpath' => false,
        ])->run();

        $this->withoutMiddleware(\App\Http\Middleware\WebConfig::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notification_provider_statuses');
        Schema::dropIfExists('notification_delivery_metrics');
        Schema::dropIfExists('notification_suppressions');

        parent::tearDown();
    }

    public function test_ses_bounce_creates_suppression_and_metric(): void
    {
        $payload = [
            'Records' => [[
                'ses' => [
                    'eventType' => 'Bounce',
                    'mail' => [
                        'destination' => ['bounced@example.com'],
                        'headers' => [
                            'X-Notification-ID' => 'uuid-2',
                        ],
                    ],
                    'bounce' => [
                        'bounceType' => 'Permanent',
                    ],
                ],
            ]],
        ];

        $request = Request::create('/api/messaging/webhooks/ses', 'POST', [], [], [], [], json_encode($payload));
        $request->headers->set('Content-Type', 'application/json');

        $controller = $this->app->make(MessagingWebhookController::class);
        $response = $controller->handle($request, 'ses');

        $this->assertSame(200, $response->getStatusCode());

        $this->assertDatabaseHas('notification_suppressions', [
            'identifier' => 'bounced@example.com',
            'reason' => 'Permanent',
        ]);

        $this->assertDatabaseHas('notification_delivery_metrics', [
            'notification_id' => 'uuid-2',
            'status' => 'failed',
        ]);
    }
}
