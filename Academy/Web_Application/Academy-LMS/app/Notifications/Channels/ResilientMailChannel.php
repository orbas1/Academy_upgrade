<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\Messaging\NotificationDeliverabilityRecorder;
use App\Services\Messaging\NotificationProviderHealthService;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResilientMailChannel extends MailChannel
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $providers;

    public function __construct(
        MailFactory $mailer,
        Markdown $markdown,
        private readonly NotificationProviderHealthService $health,
        private readonly NotificationDeliverabilityRecorder $recorder
    ) {
        parent::__construct($mailer, $markdown);
        $this->providers = $this->resolveProviders();
    }

    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toMail')) {
            return null;
        }

        foreach ($this->providers as $provider => $config) {
            if (! $this->health->isHealthy('email', $provider)) {
                Log::warning('notifications.email.provider_skipped', [
                    'provider' => $provider,
                    'reason' => 'circuit_open',
                ]);

                continue;
            }

            $message = $notification->toMail($notifiable);

            if (! $notifiable->routeNotificationFor('mail', $notification) && ! $message instanceof Mailable) {
                return null;
            }

            try {
                if ($message instanceof Mailable) {
                    if (! empty($config['mailer'])) {
                        $message->mailer($config['mailer']);
                    }

                    $result = $message->send($this->mailer);
                } else {
                    if (! empty($config['mailer'])) {
                        $message->mailer($config['mailer']);
                    }

                    $result = $this->mailer->mailer($message->mailer ?? null)->send(
                        $this->buildView($message),
                        array_merge($message->data(), $this->additionalMessageData($notification)),
                        $this->messageBuilder($notifiable, $notification, $message)
                    );
                }

                $this->health->markSuccess('email', $provider);
                $this->recorder->recordSent(
                    notificationId: $notification->id,
                    userId: method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
                    channel: 'email',
                    provider: $provider,
                    event: $this->extractEventKey($notification),
                    context: [
                        'provider' => $provider,
                        'mailer' => $config['mailer'] ?? null,
                    ]
                );

                return $result;
            } catch (Throwable $exception) {
                $this->health->markFailure('email', $provider, $exception->getMessage(), [
                    'exception' => $exception::class,
                ]);

                $this->recorder->recordFailure(
                    notificationId: $notification->id,
                    userId: method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
                    channel: 'email',
                    provider: $provider,
                    event: $this->extractEventKey($notification),
                    context: [
                        'message' => $exception->getMessage(),
                    ]
                );

                Log::error('notifications.email.provider_failed', [
                    'provider' => $provider,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Log::critical('notifications.email.all_providers_failed', [
            'providers' => array_keys($this->providers),
            'notification' => $notification::class,
        ]);

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function resolveProviders(): array
    {
        $providers = (array) config('messaging.email.providers', []);

        uasort($providers, static function ($left, $right) {
            return ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);
        });

        return $providers;
    }

    protected function extractEventKey(Notification $notification): ?string
    {
        $data = method_exists($notification, 'toArray') ? $notification->toArray(null) : [];

        if (is_array($data) && array_key_exists('event', $data)) {
            return (string) Arr::get($data, 'event');
        }

        if (property_exists($notification, 'eventKey')) {
            return (string) $notification->eventKey;
        }

        return null;
    }
}
