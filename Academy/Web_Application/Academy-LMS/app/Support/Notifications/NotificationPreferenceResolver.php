<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\CommunityNotificationPreference;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class NotificationPreferenceResolver
{
    public function __construct(private readonly ?Repository $cache = null)
    {
    }

    /**
     * Resolve preferences with per-community override fallback.
     */
    public function for(User $user, ?int $communityId = null): CommunityNotificationPreference
    {
        $cacheKey = sprintf('notifications.preferences.%s.%s', $user->getKey(), $communityId ?? 'global');

        $cache = $this->cache ?? Cache::store();

        return $cache->remember($cacheKey, now()->addMinutes(10), function () use ($user, $communityId): CommunityNotificationPreference {
            $scoped = null;

            if ($communityId !== null) {
                $scoped = CommunityNotificationPreference::query()
                    ->where('user_id', $user->getKey())
                    ->where('community_id', $communityId)
                    ->first();
            }

            $global = CommunityNotificationPreference::query()
                ->where('user_id', $user->getKey())
                ->whereNull('community_id')
                ->first();

            $preference = $scoped ?? $global ?? CommunityNotificationPreference::defaults(['user_id' => $user->getKey(), 'community_id' => $communityId]);

            if ($scoped !== null && $global !== null) {
                $preference->channel_email = $scoped->channel_email && $global->channel_email;
                $preference->channel_push = $scoped->channel_push && $global->channel_push;
                $preference->channel_in_app = $scoped->channel_in_app && $global->channel_in_app;
                $preference->digest_frequency = $scoped->digest_frequency ?: $global->digest_frequency;
                $preference->muted_events = array_values(array_unique(array_merge($global->muted_events ?? [], $scoped->muted_events ?? [])));
                $preference->locale = $scoped->locale ?? $global->locale;
            }

            return $preference;
        });
    }

    /**
     * @param array<int, int> $communityIds
     *
     * @return array<int, CommunityNotificationPreference>
     */
    public function forMany(User $user, array $communityIds): array
    {
        $communityIds = array_values(array_filter(array_unique($communityIds)));

        if ($communityIds === []) {
            return [];
        }

        return array_reduce($communityIds, function (array $carry, int $communityId) use ($user): array {
            $carry[$communityId] = $this->for($user, $communityId);

            return $carry;
        }, []);
    }

    /**
     * Invalidate cached preferences.
     */
    public function forget(User $user, ?int $communityId = null): void
    {
        $cache = $this->cache ?? Cache::store();

        $keys = [$communityId];

        if ($communityId !== null) {
            $keys[] = null;
        }

        foreach ($keys as $key) {
            $cacheKey = sprintf('notifications.preferences.%s.%s', $user->getKey(), $key ?? 'global');
            $cache->forget($cacheKey);
        }
    }
}
