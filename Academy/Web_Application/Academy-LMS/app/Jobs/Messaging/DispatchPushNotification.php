<?php

declare(strict_types=1);

namespace App\Jobs\Messaging;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchPushNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public array $payload)
    {
        $this->onQueue('notifications');
    }

    public int $tries = 3;

    public int $timeout = 10;

    public function handle(): void
    {
        $providerKey = config('messaging.push.default_provider');
        $provider = config('messaging.push.providers.'.$providerKey);

        if (! is_array($provider) || empty($provider['endpoint'])) {
            Log::warning('notifications.push.provider_missing', ['provider' => $providerKey]);

            return;
        }

        $endpoint = $provider['endpoint'];
        $token = $provider['token'] ?? null;
        $timeout = (int) ($provider['timeout'] ?? config('messaging.push.timeout', 5));

        $body = [
            'message' => $this->payload['payload'] ?? [],
            'notifiable_id' => $this->payload['notifiable_id'] ?? null,
            'notifiable_type' => $this->payload['notifiable_type'] ?? null,
        ];

        try {
            $response = Http::timeout($timeout)
                ->withToken($token)
                ->acceptJson()
                ->post($endpoint, $body);

            if ($response->failed()) {
                Log::error('notifications.push.failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'provider' => $providerKey,
                ]);

                $this->release(5);

                return;
            }
        } catch (Throwable $exception) {
            Log::error('notifications.push.exception', [
                'message' => $exception->getMessage(),
                'provider' => $providerKey,
            ]);

            $this->fail($exception);
        }
    }
}
