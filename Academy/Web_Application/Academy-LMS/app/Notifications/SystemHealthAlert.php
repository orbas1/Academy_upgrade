<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SystemHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $issues
     * @param  array<string, mixed>  $meta
     * @param  array<int, string>  $channels
     */
    public function __construct(
        private readonly array $issues,
        private readonly array $meta = [],
        private readonly array $channels = ['mail']
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('System health alert')
            ->greeting('Operations team,')
            ->line('The observability monitor detected the following issues:');

        foreach ($this->issues as $issue) {
            $mail->line(sprintf('• %s: %s', Arr::get($issue, 'type', 'issue'), Arr::get($issue, 'message', 'Unknown alert')));
        }

        $mail->line('Environment: '.($this->meta['environment'] ?? config('app.env')));
        $mail->line('Window: '.($this->meta['window_seconds'] ?? 'n/a').' seconds');
        $mail->line('Timestamp: '.Carbon::now()->toIso8601String());

        return $mail;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $message = (new SlackMessage())
            ->error()
            ->content('System health alert triggered');

        foreach ($this->issues as $issue) {
            $message->attachment(function ($attachment) use ($issue): void {
                $attachment
                    ->title(Arr::get($issue, 'type', 'issue'))
                    ->content(Arr::get($issue, 'message', ''))
                    ->fields(Arr::get($issue, 'context', []));
            });
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'issues' => $this->issues,
            'meta' => $this->meta,
        ];
    }
}
