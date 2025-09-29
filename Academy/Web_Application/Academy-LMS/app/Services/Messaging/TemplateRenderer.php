<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class TemplateRenderer
{

    /**
     * @param array<string, mixed> $data
     */
    public function buildMailMessage(
        mixed $notifiable,
        string $eventKey,
        array $data,
        ?string $template = null,
        ?string $locale = null
    ): MailMessage {
        $locale ??= method_exists($notifiable, 'preferredLocale') ? $notifiable->preferredLocale() : ($notifiable->locale ?? app()->getLocale());

        $subject = Arr::get($data, 'subject') ?: $this->translate('subject', $eventKey, $data, $locale);
        $preview = Arr::get($data, 'preview') ?: $this->translate('preview', $eventKey, $data, $locale);
        $template ??= config('messaging.email.templates.'.$eventKey.'.view', 'emails.communities.event');

        $viewData = [
            'notifiable' => $notifiable,
            'subject' => $subject,
            'preview' => $preview,
            'data' => $data,
            'cta' => Arr::get($data, 'cta', []),
            'locale' => $locale,
        ];

        $message = (new MailMessage())
            ->view($template, $viewData)
            ->subject($subject);

        if ($preview) {
            $message->line(Str::of($preview)->stripTags()->toString());
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function translate(string $type, string $eventKey, array $data, ?string $locale = null): string
    {
        $key = config('messaging.email.templates.'.$eventKey.'.'.$type);

        if (! $key) {
            return '';
        }

        $replace = [
            'community' => Arr::get($data, 'community_name', 'your community'),
            'member' => Arr::get($data, 'member_name', 'A member'),
        ];

        return Lang::get($key, $replace, $locale);
    }

}
