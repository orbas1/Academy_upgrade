<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Domain\Communities\Services\CommunitySubscriptionService as DomainSubscriptionService;
use App\Models\Community\Community;
use App\Services\Billing\Exceptions\StripeWebhookException;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;

class StripeWebhookService
{
    private ?StripeClient $client = null;

    public function __construct(
        private readonly DomainSubscriptionService $subscriptions,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function constructEvent(string $payload, ?string $signatureHeader): Event
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            throw new StripeWebhookException('Missing Stripe-Signature header.');
        }

        $secret = config('stripe.webhook_secret');
        if (!is_string($secret) || $secret === '') {
            throw new StripeWebhookException('Stripe webhook secret is not configured.');
        }

        try {
            return Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $secret,
                (int) config('stripe.webhook_tolerance', 300)
            );
        } catch (SignatureVerificationException $exception) {
            $this->logger->warning('Stripe webhook signature verification failed.', [
                'exception' => $exception,
            ]);

            throw new StripeWebhookException('Invalid Stripe webhook signature.', 0, $exception);
        }
    }

    public function handle(Event $event): void
    {
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'customer.subscription.updated' => $this->handleSubscriptionLifecycle($event),
                'customer.subscription.deleted' => $this->handleSubscriptionLifecycle($event),
                default => $this->logger->info('Received unsupported Stripe webhook event.', [
                    'type' => $event->type,
                    'id' => $event->id,
                ]),
            };
        } catch (StripeWebhookException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error('Unhandled exception while processing Stripe webhook.', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'exception' => $exception,
            ]);

            throw new StripeWebhookException('Unable to process Stripe webhook event.', 0, $exception);
        }
    }

    private function handleCheckoutSessionCompleted(Event $event): void
    {
        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;

        if (($session->mode ?? '') !== 'subscription') {
            return;
        }

        $subscriptionId = $session->subscription;
        if (!is_string($subscriptionId) || $subscriptionId === '') {
            throw new StripeWebhookException('Checkout session is missing subscription identifier.');
        }

        $metadata = $this->normalizeMetadata($session->metadata ?? []);
        $subscription = $this->retrieveSubscription($subscriptionId);

        $this->syncSubscription($event, $metadata, $subscription, [
            'paid' => ($session->payment_status ?? '') === 'paid',
            'invoice_id' => $session->invoice ?? null,
        ]);
    }

    private function handleInvoicePaymentSucceeded(Event $event): void
    {
        /** @var \Stripe\Invoice $invoice */
        $invoice = $event->data->object;
        if (!is_string($invoice->subscription) || $invoice->subscription === '') {
            throw new StripeWebhookException('Invoice is missing subscription identifier.');
        }

        $subscription = $this->retrieveSubscription($invoice->subscription);
        $metadata = $this->normalizeMetadata($invoice->metadata ?? [], $subscription['metadata'] ?? []);

        $this->syncSubscription($event, $metadata, $subscription, [
            'paid' => true,
            'invoice_id' => $invoice->id,
        ]);
    }

    private function handleInvoicePaymentFailed(Event $event): void
    {
        /** @var \Stripe\Invoice $invoice */
        $invoice = $event->data->object;
        if (!is_string($invoice->subscription) || $invoice->subscription === '') {
            throw new StripeWebhookException('Invoice is missing subscription identifier.');
        }

        $subscription = $this->retrieveSubscription($invoice->subscription);
        $metadata = $this->normalizeMetadata($invoice->metadata ?? [], $subscription['metadata'] ?? []);

        $this->syncSubscription($event, $metadata, $subscription, [
            'paid' => false,
            'invoice_id' => $invoice->id,
            'override_status' => 'past_due',
        ]);
    }

    private function handleSubscriptionLifecycle(Event $event): void
    {
        /** @var \Stripe\Subscription $subscriptionObject */
        $subscriptionObject = $event->data->object;
        $metadata = $this->normalizeMetadata($subscriptionObject->metadata ?? []);
        $subscription = $subscriptionObject->toArray(true);

        $overrideStatus = $event->type === 'customer.subscription.deleted' ? 'canceled' : null;

        $this->syncSubscription($event, $metadata, $subscription, [
            'paid' => in_array($subscriptionObject->status, ['active', 'trialing'], true),
            'override_status' => $overrideStatus,
        ]);
    }

    private function syncSubscription(Event $event, array $metadata, array $subscription, array $context = []): void
    {
        $communityId = $this->intFromMetadata($metadata, 'community_id');
        $userId = $this->intFromMetadata($metadata, 'user_id');
        $tierId = $this->intFromMetadata($metadata, 'subscription_tier_id');

        if ($communityId === null || $userId === null || $tierId === null) {
            $this->logger->warning('Stripe webhook missing application metadata.', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'metadata' => $metadata,
            ]);

            throw new StripeWebhookException('Stripe webhook missing community or user metadata.');
        }

        $community = Community::query()->find($communityId);
        if ($community === null) {
            throw new StripeWebhookException(sprintf('Community %d referenced by Stripe subscription does not exist.', $communityId));
        }

        $status = $context['override_status'] ?? $this->mapStripeStatus($subscription['status'] ?? 'active');

        $renewsAt = isset($subscription['current_period_end'])
            ? CarbonImmutable::createFromTimestamp((int) $subscription['current_period_end'])->toIso8601String()
            : null;
        $endedAt = null;
        if (isset($subscription['ended_at'])) {
            $endedAt = CarbonImmutable::createFromTimestamp((int) $subscription['ended_at'])->toIso8601String();
        } elseif (in_array($status, ['canceled', 'expired'], true)) {
            $endedAt = CarbonImmutable::now()->toIso8601String();
        }

        $payload = [
            'user_id' => $userId,
            'subscription_tier_id' => $tierId,
            'provider' => 'stripe',
            'provider_subscription_id' => $subscription['id'] ?? null,
            'status' => $status,
            'renews_at' => $renewsAt,
            'ended_at' => $endedAt,
            'metadata' => [
                'stripe' => [
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                    'payload' => $subscription,
                    'context' => $context,
                ],
            ],
            'paid' => (bool) ($context['paid'] ?? false),
        ];

        $this->subscriptions->syncFromWebhook($community, $payload);
    }

    private function retrieveSubscription(string $subscriptionId): array
    {
        try {
            return $this->stripeClient()
                ->subscriptions
                ->retrieve($subscriptionId, [])
                ->toArray(true);
        } catch (ApiErrorException $exception) {
            $this->logger->error('Unable to retrieve Stripe subscription.', [
                'subscription_id' => $subscriptionId,
                'exception' => $exception,
            ]);

            throw new StripeWebhookException('Unable to load subscription details from Stripe.', 0, $exception);
        }
    }

    private function normalizeMetadata(mixed ...$sources): array
    {
        $merged = [];
        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            if (is_object($source) && method_exists($source, 'toArray')) {
                $source = $source->toArray();
            }

            if (!is_iterable($source)) {
                continue;
            }

            foreach ($source as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $merged[(string) $key] = (string) $value;
            }
        }

        return $merged;
    }

    private function intFromMetadata(array $metadata, string $key): ?int
    {
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return filter_var($metadata[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function mapStripeStatus(string $status): string
    {
        return match ($status) {
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due', 'unpaid', 'incomplete' => 'past_due',
            'canceled' => 'canceled',
            'incomplete_expired' => 'expired',
            default => 'active',
        };
    }

    private function stripeClient(): StripeClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $secret = config('stripe.secret_key');
        if (!is_string($secret) || $secret === '') {
            throw new StripeWebhookException('Stripe secret key is not configured.');
        }

        $this->client = new StripeClient($secret);

        return $this->client;
    }
}
