<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Messaging\NotificationDeliverabilityRecorder;
use App\Services\Messaging\NotificationProviderHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MessagingWebhookController extends Controller
{
    public function __construct(
        private readonly NotificationDeliverabilityRecorder $recorder,
        private readonly NotificationProviderHealthService $health
    ) {
    }

    public function handle(Request $request, string $provider): JsonResponse
    {
        $payload = $request->all();
        $events = $this->normaliseEvents($provider, $payload);

        foreach ($events as $event) {
            $status = Arr::get($event, 'status');
            $channel = Arr::get($event, 'channel', 'email');
            $identifier = Arr::get($event, 'identifier');
            $reason = Arr::get($event, 'reason', $status);
            $notificationId = Arr::get($event, 'notification_id');
            $userId = Arr::get($event, 'user_id');
            $context = Arr::get($event, 'context', []);

            if (in_array($status, ['bounced', 'complaint', 'suppressed'], true) && $identifier) {
                $this->recorder->recordSuppression($channel, $identifier, (string) $reason, $provider, $context);
            }

            if (in_array($status, ['bounced', 'complaint', 'failed'], true)) {
                $this->recorder->recordFailure($notificationId, $userId, $channel, $provider, Arr::get($event, 'event'), $context);
                $this->health->markFailure($channel, $provider, $reason ?? $status, $context);
            } elseif ($status === 'delivered') {
                $this->recorder->recordSent($notificationId, $userId, $channel, $provider, Arr::get($event, 'event'), $context);
                $this->health->markSuccess($channel, $provider);
            }
        }

        Log::info('messaging.webhook.processed', [
            'provider' => $provider,
            'count' => count($events),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseEvents(string $provider, array $payload): array
    {
        return match ($provider) {
            'ses' => $this->transformSes($payload),
            'resend' => $this->transformResend($payload),
            default => $this->transformGeneric($payload, $provider),
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    protected function transformSes(array $payload): array
    {
        $records = Arr::get($payload, 'Records', []);

        return collect($records)
            ->map(function ($record) {
                $ses = Arr::get($record, 'ses', []);
                $mail = Arr::get($ses, 'mail', []);
                $eventType = Arr::get($ses, 'eventType');
                $recipients = Arr::get($mail, 'destination', []);

                $status = match ($eventType) {
                    'Bounce' => 'bounced',
                    'Complaint' => 'complaint',
                    'Delivery' => 'delivered',
                    default => 'unknown',
                };

                return [
                    'status' => $status,
                    'channel' => 'email',
                    'identifier' => Arr::first($recipients),
                    'reason' => Arr::get($ses, 'bounce.bounceType') ?? Arr::get($ses, 'complaint.complaintFeedbackType'),
                    'notification_id' => Arr::get($mail, 'headers.X-Notification-ID'),
                    'context' => $ses,
                ];
            })
            ->filter(fn ($event) => $event['status'] !== 'unknown')
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    protected function transformResend(array $payload): array
    {
        $events = Arr::get($payload, 'events', []);

        return collect($events)
            ->map(function ($event) {
                $type = Arr::get($event, 'type');
                $status = match ($type) {
                    'email.delivered' => 'delivered',
                    'email.bounced' => 'bounced',
                    'email.complained' => 'complaint',
                    default => 'unknown',
                };

                return [
                    'status' => $status,
                    'channel' => 'email',
                    'identifier' => Arr::get($event, 'data.email'),
                    'reason' => Arr::get($event, 'data.reason'),
                    'notification_id' => Arr::get($event, 'data.metadata.__laravel_notification_id'),
                    'context' => $event,
                ];
            })
            ->filter(fn ($event) => $event['status'] !== 'unknown')
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    protected function transformGeneric(array $payload, string $provider): array
    {
        return [[
            'status' => Arr::get($payload, 'status', 'unknown'),
            'channel' => Arr::get($payload, 'channel', 'email'),
            'identifier' => Arr::get($payload, 'identifier'),
            'reason' => Arr::get($payload, 'reason'),
            'notification_id' => Arr::get($payload, 'notification_id'),
            'context' => $payload,
        ]];
    }
}
