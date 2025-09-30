<?php

namespace App\Domain\Communities\Services;

use App\Domain\Analytics\Services\AnalyticsDispatcher;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityFollow;
use App\Models\User;
use Carbon\CarbonImmutable;

class CommunityFollowService
{
    public function __construct(private readonly AnalyticsDispatcher $analytics)
    {
    }

    public function followCommunity(Community $community, User $user, bool $notifications = true): CommunityFollow
    {
        $follow = CommunityFollow::updateOrCreate(
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

        $this->analytics->record('follow_add', $user, [
            'community_id' => $community->getKey(),
            'notifications' => $notifications,
        ], $community);

        return $follow;
    }

    public function unfollowCommunity(Community $community, User $user): void
    {
        CommunityFollow::query()
            ->where('follower_id', $user->getKey())
            ->where('followable_type', Community::class)
            ->where('followable_id', $community->getKey())
            ->delete();

        $this->analytics->record('follow_remove', $user, [
            'community_id' => $community->getKey(),
        ], $community);
    }

    public function updateNotificationPreference(CommunityFollow $follow, bool $enabled): CommunityFollow
    {
        $follow->notifications_enabled = $enabled;
        $follow->save();

        $follow->loadMissing('follower', 'followable');

        $this->analytics->record('follow_notifications_update', $follow->follower, [
            'community_id' => $follow->community_id,
            'notifications_enabled' => $enabled,
        ], $follow->followable instanceof Community ? $follow->followable : null);

        return $follow;
    }
}

