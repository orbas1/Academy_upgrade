<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityFollow;
use App\Models\User;
use Carbon\CarbonImmutable;

class CommunityFollowService
{
    public function followCommunity(Community $community, User $user, bool $notifications = true): CommunityFollow
    {
        return CommunityFollow::updateOrCreate(
            [
                'follower_id' => $user->getKey(),
                'followable_type' => Community::class,
                'followable_id' => $community->getKey(),
            ],
            [
                'community_id' => $community->getKey(),
                'notifications_enabled' => $notifications,
                'followed_at' => CarbonImmutable::now(),
            ]
        );
    }

    public function unfollowCommunity(Community $community, User $user): void
    {
        CommunityFollow::query()
            ->where('follower_id', $user->getKey())
            ->where('followable_type', Community::class)
            ->where('followable_id', $community->getKey())
            ->delete();
    }

    public function updateNotificationPreference(CommunityFollow $follow, bool $enabled): CommunityFollow
    {
        $follow->notifications_enabled = $enabled;
        $follow->save();

        return $follow;
    }
}

