<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\CommunityFollow;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EloquentFollowService implements FollowService
{
    public function followMember(CommunityMember $follower, CommunityMember $followed): CommunityFollow
    {
        $this->assertSameCommunity($follower, $followed);

        if ($follower->getKey() === $followed->getKey()) {
            throw new InvalidArgumentException('Members cannot follow themselves.');
        }

        return CommunityFollow::updateOrCreate(
            [
                'follower_id' => $follower->user_id,
                'followable_type' => CommunityMember::class,
                'followable_id' => $followed->getKey(),
            ],
            [
                'community_id' => $followed->community_id,
                'notifications_enabled' => true,
                'followed_at' => CarbonImmutable::now(),
                'metadata' => [
                    'relationship' => 'member',
                ],
            ]
        );
    }

    public function unfollowMember(CommunityMember $follower, CommunityMember $followed): void
    {
        $this->assertSameCommunity($follower, $followed);

        CommunityFollow::query()
            ->where('follower_id', $follower->user_id)
            ->where('followable_type', CommunityMember::class)
            ->where('followable_id', $followed->getKey())
            ->delete();
    }

    public function toggleFollow(CommunityMember $follower, CommunityMember $followed): CommunityFollow
    {
        $existing = CommunityFollow::query()
            ->where('follower_id', $follower->user_id)
            ->where('followable_type', CommunityMember::class)
            ->where('followable_id', $followed->getKey())
            ->first();

        if ($existing) {
            $existing->delete();
            return $existing;
        }

        return $this->followMember($follower, $followed);
    }

    public function getFollowers(CommunityMember $member): Collection
    {
        return CommunityFollow::query()
            ->where('followable_type', CommunityMember::class)
            ->where('followable_id', $member->getKey())
            ->with('follower:id,name,email,profile_photo_path,photo')
            ->orderByDesc('followed_at')
            ->get();
    }

    public function getFollowing(CommunityMember $member): Collection
    {
        return CommunityFollow::query()
            ->where('follower_id', $member->user_id)
            ->where('followable_type', CommunityMember::class)
            ->with('followable')
            ->orderByDesc('followed_at')
            ->get();
    }

    public function syncExternalFollowers(User $user, CommunityMember $member): void
    {
        CommunityFollow::updateOrCreate(
            [
                'follower_id' => $user->getKey(),
                'followable_type' => CommunityMember::class,
                'followable_id' => $member->getKey(),
            ],
            [
                'community_id' => $member->community_id,
                'notifications_enabled' => true,
                'followed_at' => CarbonImmutable::now(),
                'metadata' => [
                    'synced_from' => 'external',
                ],
            ]
        );

        $memberMetadata = $member->metadata ?? [];
        $memberMetadata['external_follow_sync_at'] = CarbonImmutable::now()->toIso8601String();
        $member->metadata = $memberMetadata;
        $member->save();
    }

    private function assertSameCommunity(CommunityMember $follower, CommunityMember $followed): void
    {
        if ((int) $follower->community_id !== (int) $followed->community_id) {
            throw new InvalidArgumentException('Members must belong to the same community.');
        }
    }
}
