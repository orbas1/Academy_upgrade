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
        public array $channels = ['database']
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
        ];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->subject())
            ->line(Arr::get($this->data, 'message', 'You have a new community update.'))
            ->action(
                Arr::get($this->data, 'cta.label', 'View update'),
                Arr::get($this->data, 'cta.url', url('/communities'))
            );

        if ($preview = Arr::get($this->data, 'preview')) {
            $mail->line((string) $preview);
        }

        return $mail;
    }

    protected function subject(): string
    {
        return Arr::get($this->data, 'subject', 'Community update');
    }
}
