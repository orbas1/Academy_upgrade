<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Jobs\Messaging\DispatchPushNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushNotificationChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            Log::debug('notifications.push.skipped', [
                'reason' => 'notification_missing_toPush',
                'notification' => $notification::class,
            ]);

            return;
        }

        $payload = $notification->toPush($notifiable);

        if (empty($payload)) {
            Log::debug('notifications.push.skipped', [
                'reason' => 'empty_payload',
                'notification' => $notification::class,
            ]);

            return;
        }

        DispatchPushNotification::dispatch([
            'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            'notifiable_type' => $notifiable::class,
            'payload' => $payload,
        ]);
    }
}
