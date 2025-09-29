<?php

declare(strict_types=1);

namespace App\Notifications\Community;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class CommunityDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<int, array<string, mixed>> $items
     * @param list<string|class-string<\Illuminate\Notifications\Notification>> $channels
     */
    public function __construct(
        public int $communityId,
        public string $frequency,
        public array $items,
        public array $channels = ['mail', 'database']
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
            'frequency' => $this->frequency,
            'items' => $this->items,
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $renderer = app(\App\Services\Messaging\TemplateRenderer::class);

        $template = config('messaging.email.templates.'.str_replace('.', '\\.', 'digest.'.$this->frequency).'.view');

        return $renderer->buildMailMessage(
            notifiable: $notifiable,
            eventKey: 'digest.'.$this->frequency,
            data: [
                'subject' => __('notifications.digest.'.$this->frequency.'.subject'),
                'items' => $this->items,
                'community_name' => Arr::get($this->items, '0.community_name', ''),
                'frequency' => $this->frequency,
                'cta' => [
                    'label' => __('notifications.digest.'.$this->frequency.'.cta'),
                    'url' => app(\App\Services\Messaging\DeepLinkResolver::class)->webUrlForEvent('digest.'.$this->frequency, [
                        'community_id' => $this->communityId,
                    ]),
                ],
            ],
            template: $template
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPush(mixed $notifiable): array
    {
        return [
            'title' => __('notifications.digest.'.$this->frequency.'.subject'),
            'body' => __('notifications.digest.'.$this->frequency.'.preview'),
            'community_id' => $this->communityId,
            'event' => 'digest.'.$this->frequency,
            'items' => array_slice($this->items, 0, 5),
        ];
    }
}
