<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CommunityNotificationPreference;
use App\Notifications\Community\CommunityDigestNotification;
use App\Services\Messaging\NotificationRouter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendCommunityDigest extends Command
{
    protected $signature = 'communities:send-digest {frequency=daily : Digest frequency (daily|weekly)} {--dry-run : Output recipients without sending notifications}';

    protected $description = 'Dispatch community digest notifications based on user preferences.';

    public function __construct(private readonly NotificationRouter $router)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $frequency = $this->argument('frequency');

        if (! is_string($frequency) || ! array_key_exists($frequency, config('messaging.digests.frequencies', []))) {
            $this->error('Invalid frequency. Supported values: '.implode(', ', array_keys(config('messaging.digests.frequencies', []))));

            return self::FAILURE;
        }

        $window = CarbonInterval::make(config('messaging.digests.window_overrides.'.$frequency) ?? config('messaging.digests.frequencies.'.$frequency, 'P1D'));
        $since = CarbonImmutable::now()->sub($window);

        $preferences = CommunityNotificationPreference::query()
            ->with('user')
            ->where('digest_frequency', $frequency)
            ->get();

        $this->info(sprintf('Found %d preference rows for %s digest', $preferences->count(), $frequency));

        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        foreach ($preferences as $preference) {
            $user = $preference->user;

            if (! $user) {
                continue;
            }

            $communityId = $preference->community_id;
            $notificationsQuery = $user->notifications()
                ->where('created_at', '>=', $since)
                ->latest();

            if ($communityId) {
                $notificationsQuery->where('data->community_id', $communityId);
            }

            $notifications = $notificationsQuery
                ->take(config('messaging.digests.max_items', 25))
                ->get();

            if ($notifications->isEmpty()) {
                continue;
            }

            $items = $notifications->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'subject' => Arr::get($data, 'subject', ''),
                    'message' => Arr::get($data, 'message', ''),
                    'cta' => Arr::get($data, 'cta', []),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'community_id' => Arr::get($data, 'community_id'),
                    'community_name' => Arr::get($data, 'community_name'),
                ];
            })->values()->all();

            $payload = $this->router->preparePayload([
                'community_id' => $communityId ?? Arr::get($items, '0.community_id'),
                'event' => 'digest.'.$frequency,
                'data' => [
                    'items' => $items,
                    'subject' => __('notifications.digest.'.$frequency.'.subject'),
                    'preview' => __('notifications.digest.'.$frequency.'.preview'),
                ],
            ]);

            $resolvedCommunityId = $communityId ?? (int) (Arr::get($items, '0.community_id') ?? 0);
            $notification = $this->router->makeNotification($user, $resolvedCommunityId, 'digest.'.$frequency, $payload);

            if (! $notification instanceof CommunityDigestNotification) {
                $notification = new CommunityDigestNotification($resolvedCommunityId, $frequency, $items, $this->router->channelsFor($user, $resolvedCommunityId, 'digest.'.$frequency, $payload));
            }

            if ($dryRun) {
                $this->line(sprintf('Would send %s digest to user #%s (%s)', $frequency, $user->getKey(), $user->email));

                continue;
            }

            Notification::send($user, $notification);
            $sent++;
        }

        Log::info('community.digest.sent', ['frequency' => $frequency, 'count' => $sent]);
        $this->info(sprintf('Dispatched %d digest notifications', $sent));

        return self::SUCCESS;
    }
}
