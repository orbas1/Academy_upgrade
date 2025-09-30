<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Communities\Models\Community;
use App\Jobs\Analytics\DeliverAnalyticsEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;

class AnalyticsDispatcher
{
    public function __construct(private readonly Dispatcher $events)
    {
    }

    public function record(
        string $eventName,
        User $user,
        array $payload = [],
        ?Community $community = null,
        ?CarbonImmutable $occurredAt = null,
        ?string $group = null
    ): ?AnalyticsEvent {
        if (! Config::get('analytics.enabled', true)) {
            return null;
        }

        if ($this->consentRequired() && ! $this->hasConsent($user)) {
            return null;
        }

        $occurredAt ??= CarbonImmutable::now();
        $hash = $this->buildUserHash($user);

        $event = AnalyticsEvent::create([
            'event_name' => $eventName,
            'event_group' => $group,
            'user_id' => $user->getKey(),
            'user_hash' => $hash,
            'community_id' => $community?->getKey(),
            'payload' => $payload,
            'occurred_at' => $occurredAt,
            'recorded_at' => CarbonImmutable::now(),
        ]);

        Bus::dispatch(new DeliverAnalyticsEvent($event->getKey()));

        $this->events->dispatch("analytics.recorded:{$eventName}", [$event]);

        return $event;
    }

    public function anonymised(string $eventName, array $payload = [], ?CarbonImmutable $occurredAt = null, ?string $group = null): AnalyticsEvent
    {
        $occurredAt ??= CarbonImmutable::now();

        $event = AnalyticsEvent::create([
            'event_name' => $eventName,
            'event_group' => $group,
            'payload' => $payload,
            'occurred_at' => $occurredAt,
            'recorded_at' => CarbonImmutable::now(),
            'delivery_status' => 'pending',
        ]);

        Bus::dispatch(new DeliverAnalyticsEvent($event->getKey()));

        return $event;
    }

    private function consentRequired(): bool
    {
        return (bool) Config::get('analytics.consent.required', true);
    }

    private function hasConsent(User $user): bool
    {
        if (! $this->consentRequired()) {
            return true;
        }

        if ($user->analytics_consent_revoked_at) {
            return false;
        }

        if (! $user->analytics_consent_at) {
            return false;
        }

        $requiredVersion = (string) Config::get('analytics.consent.version');
        if ($requiredVersion === '') {
            return true;
        }

        return $user->analytics_consent_version === $requiredVersion;
    }

    private function buildUserHash(User $user): string
    {
        $hashKey = (string) Config::get('analytics.hash_key');
        $payload = sprintf('%s|%s|%s', $user->getKey(), $user->email, $hashKey);

        return hash('sha256', $payload);
    }
}
