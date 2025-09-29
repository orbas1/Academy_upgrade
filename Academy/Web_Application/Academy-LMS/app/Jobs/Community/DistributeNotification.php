<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Models\User;
use App\Notifications\Community\CommunityEventNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DistributeNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $recipientIds = Collection::make(Arr::get($this->payload, 'recipient_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            Log::debug('community.notifications.distribute.skipped', [
                'reason' => 'no_recipients',
                'payload' => $this->payload,
            ]);

            return;
        }

        $communityId = Arr::get($this->payload, 'community_id');

        if (! $communityId) {
            Log::warning('community.notifications.distribute.skipped', [
                'reason' => 'missing_community',
                'payload' => $this->payload,
            ]);

            return;
        }

        $users = User::query()->whereIn('id', $recipientIds)->get();

        if ($users->isEmpty()) {
            Log::debug('community.notifications.distribute.skipped', [
                'reason' => 'users_not_found',
                'recipient_ids' => $recipientIds,
            ]);

            return;
        }

        $notification = new CommunityEventNotification(
            communityId: (int) $communityId,
            eventKey: (string) Arr::get($this->payload, 'event', 'community.generic'),
            data: Arr::get($this->payload, 'data', []),
            channels: Arr::get($this->payload, 'channels', ['database'])
        );

        Notification::send($users, $notification);

        Log::info('community.notifications.distribute.sent', [
            'community_id' => $communityId,
            'recipient_count' => $users->count(),
            'event' => $notification->eventKey,
        ]);
    }
}
