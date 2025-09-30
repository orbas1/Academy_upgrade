<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Services\Billing\Exceptions\StripeWebhookException;
use App\Services\Billing\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Event;
use Tests\TestCase;

class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_successful_webhook_dispatches_to_service(): void
    {
        $event = Event::constructFrom([
            'id' => 'evt_test',
            'type' => 'checkout.session.completed',
        ]);

        $service = Mockery::mock(StripeWebhookService::class);
        $service->shouldReceive('constructEvent')
            ->once()
            ->with('{"status":"ok"}', 'sig_test')
            ->andReturn($event);
        $service->shouldReceive('handle')
            ->once()
            ->with($event);

        $this->app->instance(StripeWebhookService::class, $service);

        $response = $this->withHeaders(['Stripe-Signature' => 'sig_test'])
            ->post('/api/billing/stripe/webhook', ['status' => 'ok']);

        $response->assertOk();
        $response->assertJson(['received' => true]);
    }

    public function test_invalid_signature_returns_bad_request(): void
    {
        $service = Mockery::mock(StripeWebhookService::class);
        $service->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new StripeWebhookException('Invalid signature.'));

        $this->app->instance(StripeWebhookService::class, $service);

        $response = $this->withHeaders(['Stripe-Signature' => 'sig_bad'])
            ->post('/api/billing/stripe/webhook', ['status' => 'ok']);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Invalid signature.']);
    }

    public function test_unexpected_exception_returns_server_error(): void
    {
        $service = Mockery::mock(StripeWebhookService::class);
        $service->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \RuntimeException('boom'));

        $this->app->instance(StripeWebhookService::class, $service);

        $response = $this->withHeaders(['Stripe-Signature' => 'sig_test'])
            ->post('/api/billing/stripe/webhook', ['status' => 'ok']);

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Unexpected error processing Stripe webhook.']);
    }
}
