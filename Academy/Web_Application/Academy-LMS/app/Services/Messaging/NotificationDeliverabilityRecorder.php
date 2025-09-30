<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\NotificationDeliveryMetric;
use App\Models\NotificationSuppression;
use Carbon\CarbonImmutable;

class NotificationDeliverabilityRecorder
{
    public function recordSent(
        ?string $notificationId,
        ?int $userId,
        string $channel,
        ?string $provider,
        ?string $event,
        array $context = []
    ): void {
        NotificationDeliveryMetric::record(
            notificationId: $notificationId,
            userId: $userId,
            channel: $channel,
            status: 'sent',
            provider: $provider,
            event: $event,
            context: $context,
        );
    }

    public function recordFailure(
        ?string $notificationId,
        ?int $userId,
        string $channel,
        ?string $provider,
        ?string $event,
        array $context = []
    ): void {
        NotificationDeliveryMetric::record(
            notificationId: $notificationId,
            userId: $userId,
            channel: $channel,
            status: 'failed',
            provider: $provider,
            event: $event,
            context: $context,
        );
    }

    public function recordSuppression(string $channel, string $identifier, string $reason, ?string $provider, array $payload = []): void
    {
        NotificationSuppression::query()->updateOrCreate(
            [
                'channel' => $channel,
                'identifier' => $identifier,
            ],
            [
                'reason' => $reason,
                'provider' => $provider,
                'payload' => $payload,
                'suppressed_at' => CarbonImmutable::now(),
            ]
        );
    }
}
