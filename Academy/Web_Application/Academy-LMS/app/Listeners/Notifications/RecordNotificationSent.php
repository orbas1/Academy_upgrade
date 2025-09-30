<?php

declare(strict_types=1);

namespace App\Listeners\Notifications;

use App\Services\Messaging\NotificationDeliverabilityRecorder;
use Illuminate\Notifications\Events\NotificationSent;
use Symfony\Component\Mailer\SentMessage;

class RecordNotificationSent
{
    public function __construct(private readonly NotificationDeliverabilityRecorder $recorder)
    {
    }

    public function handle(NotificationSent $event): void
    {
        $channels = (array) $event->channel;
        $channel = $channels[0] ?? 'unknown';

        $provider = $event->response instanceof SentMessage
            ? optional($event->response->getEnvelope()->getSender())->getHost()
            : null;

        $notification = $event->notification;
        $data = method_exists($notification, 'toArray') ? $notification->toArray($event->notifiable) : [];
        $eventKey = is_array($data) ? ($data['event'] ?? null) : null;

        $this->recorder->recordSent(
            notificationId: $notification->id,
            userId: method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            channel: (string) $channel,
            provider: $provider,
            event: $eventKey,
            context: [
                'response_class' => is_object($event->response) ? $event->response::class : null,
            ]
        );
    }
}
