<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Templates;

use App\Domain\Messaging\Data\CommunityEmailMessage;
use App\Domain\Messaging\Data\CommunityPushMessage;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class CommunityNotificationTemplateRepository
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @var array<string, mixed>
     */
    protected array $brand;

    public function __construct(
        protected Translator $translator,
        ?array $config = null,
        ?array $brand = null
    ) {
        $this->config = $config ?? (array) config('messaging.community', []);
        $this->brand = $brand ?? (array) config('messaging.brand', []);
    }

    /**
     * @return array<int, string>
     */
    public function supportedEvents(): array
    {
        return array_keys($this->config['events'] ?? []);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildEmailMessage(string $event, array $context, ?string $locale = null): CommunityEmailMessage
    {
        $eventConfig = $this->config['events'][$event] ?? null;

        if (! $eventConfig) {
            throw new InvalidArgumentException(sprintf('Unknown community messaging event [%s]', $event));
        }

        $locale = $this->resolveLocale($locale);
        $context = $this->normaliseContext($context);

        $subject = $this->translator->get("community.{$event}.subject", $context, $locale);
        $preview = $this->translator->get("community.{$event}.preview", $context, $locale);

        $preferences = $context['preferencesUrl'] ?? $this->config['preferences_url'] ?? '/settings/notifications';

        $data = array_merge($context, [
            'brand' => $this->brandMeta(),
            'title' => $subject,
            'previewText' => $preview,
            'preferencesUrl' => URL::to($preferences),
        ]);

        return new CommunityEmailMessage(
            event: $event,
            subject: $subject,
            view: $eventConfig['email_view'],
            data: $data,
            previewText: $preview,
            locale: $locale,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildPushMessage(string $event, array $context, ?string $locale = null): CommunityPushMessage
    {
        $this->ensureEventExists($event);

        $locale = $this->resolveLocale($locale);
        $context = $this->normaliseContext($context);

        $title = $this->translator->get("community.{$event}.push.title", $context, $locale);
        $body = $this->translator->get("community.{$event}.push.body", $context, $locale);

        $payload = array_filter([
            'category' => $event,
            'community' => $context['community'] ?? null,
            'deeplink' => $context['deepLink'] ?? $context['actionUrl'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        return new CommunityPushMessage(
            event: $event,
            title: $title,
            body: $body,
            data: array_merge($payload, Arr::only($context, ['recipient', 'platform'])),
            locale: $locale,
        );
    }

    protected function resolveLocale(?string $locale): string
    {
        $locale ??= app()->getLocale();

        $supported = $this->config['supported_locales'] ?? [];

        if (empty($supported)) {
            return $locale;
        }

        if (in_array($locale, $supported, true)) {
            return $locale;
        }

        return $this->config['default_locale'] ?? 'en';
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function normaliseContext(array $context): array
    {
        if (! isset($context['platform'])) {
            $context['platform'] = $this->brand['name'] ?? config('app.name');
        }

        if (isset($context['communityName']) && ! isset($context['community'])) {
            $context['community'] = $context['communityName'];
        }

        if (isset($context['recipientName']) && ! isset($context['recipient'])) {
            $context['recipient'] = $context['recipientName'];
        }

        if (isset($context['inviterName']) && ! isset($context['inviter'])) {
            $context['inviter'] = $context['inviterName'];
        }

        if (isset($context['actorName']) && ! isset($context['actor'])) {
            $context['actor'] = $context['actorName'];
        }

        if (isset($context['eventName']) && ! isset($context['event'])) {
            $context['event'] = $context['eventName'];
        }

        if (isset($context['periodLabel']) && ! isset($context['period'])) {
            $context['period'] = $context['periodLabel'];
        }

        if (isset($context['action_url']) && ! isset($context['actionUrl'])) {
            $context['actionUrl'] = $context['action_url'];
        }

        if (! isset($context['deepLink']) && isset($context['deeplink'])) {
            $context['deepLink'] = $context['deeplink'];
        }

        return $context;
    }

    protected function ensureEventExists(string $event): void
    {
        if (! isset($this->config['events'][$event])) {
            throw new InvalidArgumentException(sprintf('Unknown community messaging event [%s]', $event));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function brandMeta(): array
    {
        $light = $this->brand['light_logo'] ?? null;
        $dark = $this->brand['dark_logo'] ?? null;

        return [
            'name' => $this->brand['name'] ?? config('app.name'),
            'support_email' => $this->brand['support_email'] ?? null,
            'light_logo_url' => $light ? URL::to($light) : null,
            'dark_logo_url' => $dark ? URL::to($dark) : null,
        ];
    }
}
