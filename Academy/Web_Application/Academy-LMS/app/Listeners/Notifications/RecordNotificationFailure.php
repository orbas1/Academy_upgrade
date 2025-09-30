<?php

declare(strict_types=1);

namespace App\Listeners\Notifications;

use App\Services\Messaging\NotificationDeliverabilityRecorder;
use Illuminate\Notifications\Events\NotificationFailed;

class RecordNotificationFailure
{
    public function __construct(private readonly NotificationDeliverabilityRecorder $recorder)
    {
    }

    public function handle(NotificationFailed $event): void
    {
        $channels = (array) $event->channel;
        $channel = $channels[0] ?? 'unknown';

        $notification = $event->notification;
        $data = method_exists($notification, 'toArray') ? $notification->toArray($event->notifiable) : [];
        $eventKey = is_array($data) ? ($data['event'] ?? null) : null;

        $this->recorder->recordFailure(
            notificationId: $notification->id,
            userId: method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : null,
            channel: (string) $channel,
            provider: null,
            event: $eventKey,
            context: [
                'response' => $event->response,
            ]
        );
    }
}
