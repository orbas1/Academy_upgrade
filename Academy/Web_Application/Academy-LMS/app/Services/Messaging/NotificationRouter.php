<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\User;
use App\Notifications\Channels\PushNotificationChannel;
use App\Notifications\Channels\ResilientMailChannel;
use App\Notifications\Community\CommunityDigestNotification;
use App\Notifications\Community\CommunityEventNotification;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Support\Arr;

class NotificationRouter
{
    public function __construct(
        private readonly NotificationPreferenceResolver $preferences,
        private readonly DeepLinkResolver $deepLinkResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function preparePayload(array $payload): array
    {
        $payload['data'] = Arr::get($payload, 'data', []);
        $payload['meta'] = Arr::get($payload, 'meta', []);

        $eventKey = (string) Arr::get($payload, 'event', 'community.generic');
        $context = [
            'community_id' => Arr::get($payload, 'community_id'),
            'data' => $payload['data'],
        ];

        if (! Arr::get($payload, 'data.cta.deep_link')) {
            if ($deepLink = $this->deepLinkResolver->forEvent($eventKey, $context)) {
                Arr::set($payload, 'data.cta.deep_link', $deepLink);
            }
        }

        if (! Arr::get($payload, 'data.cta.url')) {
            if ($webUrl = $this->deepLinkResolver->webUrlForEvent($eventKey, $context)) {
                Arr::set($payload, 'data.cta.url', $webUrl);
            }
        }

        $templateConfig = $this->templateConfig($eventKey);

        if (! Arr::get($payload, 'data.subject')) {
            $subjectKey = Arr::get($templateConfig, 'subject');
            Arr::set($payload, 'data.subject', __($subjectKey ?? 'notifications.community.generic.subject', [
                'community' => Arr::get($payload, 'data.community_name', 'your community'),
            ]));
        }

        if (! Arr::get($payload, 'data.preview')) {
            $previewKey = Arr::get($templateConfig, 'preview');
            if ($previewKey) {
                Arr::set($payload, 'data.preview', __($previewKey, [
                    'community' => Arr::get($payload, 'data.community_name', 'your community'),
                ]));
            }
        }

        return $payload;
    }

    /**
     * Determine notification channels for a user.
     *
     * @param array<string, mixed> $payload
     *
     * @return list<string|class-string<\Illuminate\Notifications\Notification>>
     */
    public function channelsFor(User $user, int $communityId, string $eventKey, array $payload = []): array
    {
        $preference = $this->preferences->for($user, $communityId);
        $channels = Arr::get($payload, 'channels', []);

        if (! in_array('database', $channels, true)) {
            $channels[] = 'database';
        }

        if ($preference->wantsEmailFor($eventKey)) {
            $channels[] = ResilientMailChannel::class;
        }

        $isDigestEvent = str_starts_with($eventKey, 'digest.');

        if ($preference->wantsPushFor($eventKey)) {
            $channels[] = PushNotificationChannel::class;
        }

        if ($isDigestEvent) {
            $preferredFrequency = $preference->digest_frequency;

            if ($preferredFrequency === 'off' || substr($eventKey, strlen('digest.')) !== $preferredFrequency) {
                $channels = array_filter($channels, static fn ($channel) => ! in_array($channel, [ResilientMailChannel::class, PushNotificationChannel::class], true));
            }
        }

        if (! $preference->wantsInAppFor($eventKey)) {
            $channels = array_filter($channels, static fn ($channel) => $channel !== 'database');
        }

        return array_values(array_unique($channels, SORT_REGULAR));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function makeNotification(User $user, int $communityId, string $eventKey, array $payload): ?CommunityEventNotification
    {
        $channels = $this->channelsFor($user, $communityId, $eventKey, $payload);

        if ($channels === []) {
            return null;
        }

        $templateConfig = $this->templateConfig($eventKey);

        $meta = array_merge(
            Arr::get($payload, 'meta', []),
            [
                'locale' => $user->preferred_locale ?? null,
                'template' => Arr::get($templateConfig, 'view'),
            ]
        );

        if (str_starts_with($eventKey, 'digest.')) {
            return new CommunityDigestNotification(
                communityId: $communityId,
                frequency: substr($eventKey, strlen('digest.')),
                items: $payload['data']['items'] ?? [],
                channels: $channels,
            );
        }

        return new CommunityEventNotification(
            communityId: $communityId,
            eventKey: $eventKey,
            data: $payload['data'] ?? [],
            channels: $channels,
            meta: $meta,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function templateConfig(string $eventKey): array
    {
        $path = 'messaging.email.templates.'.str_replace('.', '\\.', $eventKey);

        return (array) config($path, []);
    }
}
