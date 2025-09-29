<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\CommunityNotificationPreference;
use App\Models\User;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Support\Arr;

class NotificationPreferenceService
{
    public function __construct(private readonly NotificationPreferenceResolver $resolver)
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(User $user, ?int $communityId, array $attributes): CommunityNotificationPreference
    {
        $preference = CommunityNotificationPreference::query()
            ->firstOrNew([
                'user_id' => $user->getKey(),
                'community_id' => $communityId,
            ]);

        foreach (['channel_email', 'channel_push', 'channel_in_app', 'digest_frequency', 'locale'] as $field) {
            if (Arr::has($attributes, $field)) {
                $preference->{$field} = Arr::get($attributes, $field);
            }
        }

        if (Arr::has($attributes, 'muted_events')) {
            $preference->muted_events = array_values(array_unique(array_map('strval', Arr::get($attributes, 'muted_events', []))));
        }

        $preference->save();

        $this->resolver->forget($user, $communityId);

        return $preference->refresh();
    }

    public function delete(User $user, ?int $communityId): void
    {
        CommunityNotificationPreference::query()
            ->where('user_id', $user->getKey())
            ->where('community_id', $communityId)
            ->delete();

        $this->resolver->forget($user, $communityId);
    }
}
