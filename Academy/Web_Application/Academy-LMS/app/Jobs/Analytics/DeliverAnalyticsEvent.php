<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Domain\Analytics\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverAnalyticsEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $eventId)
    {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $event = AnalyticsEvent::query()->find($this->eventId);

        if (! $event) {
            return;
        }

        $segmentKey = Config::get('analytics.segment.write_key');
        $segmentEndpoint = Config::get('analytics.segment.endpoint');

        if (! $segmentKey || ! $segmentEndpoint) {
            $event->markDelivered('skipped');

            return;
        }

        $payload = [
            'userId' => $event->user_hash ?: Arr::get($event->payload, 'anonymous_id', 'anon'),
            'event' => $event->event_name,
            'properties' => $event->payload ?? [],
            'timestamp' => optional($event->occurred_at)->toIso8601String(),
            'context' => [
                'groupId' => $event->event_group,
                'traits' => [
                    'community_id' => $event->community_id,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->withBasicAuth($segmentKey, '')
                ->timeout((float) Config::get('analytics.segment.timeout', 2.0))
                ->post($segmentEndpoint, $payload);

            if (! $response->successful()) {
                $event->markDelivered('failed', sprintf('HTTP %s: %s', $response->status(), $response->body()));

                return;
            }

            $event->markDelivered();
        } catch (Throwable $exception) {
            $event->markDelivered('failed', $exception->getMessage());

            throw $exception;
        }
    }
}
