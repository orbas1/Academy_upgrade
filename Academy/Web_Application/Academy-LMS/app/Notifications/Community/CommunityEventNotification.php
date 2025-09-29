<?php

declare(strict_types=1);

namespace App\Notifications\Community;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class CommunityEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $data
     * @param list<string> $channels
     */
    public function __construct(
        public int $communityId,
        public string $eventKey,
        public array $data = [],
        public array $channels = ['database'],
        public array $meta = []
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return $this->channels;
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'community_id' => $this->communityId,
            'event' => $this->eventKey,
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $renderer = app(\App\Services\Messaging\TemplateRenderer::class);
        $template = $this->meta['template'] ?? null;
        $locale = $this->meta['locale'] ?? null;

        return $renderer->buildMailMessage(
            notifiable: $notifiable,
            eventKey: $this->eventKey,
            data: $this->data,
            template: $template,
            locale: $locale,
        );
    }

    protected function subject(): string
    {
        return Arr::get($this->data, 'subject', 'Community update');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPush(mixed $notifiable): array
    {
        return [
            'title' => Arr::get($this->data, 'subject', 'Community update'),
            'body' => Arr::get($this->data, 'message', ''),
            'community_id' => $this->communityId,
            'event' => $this->eventKey,
            'cta' => Arr::get($this->data, 'cta', []),
        ];
    }
}
