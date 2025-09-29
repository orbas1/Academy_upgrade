<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\CommunityFollow;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contract covering follow graph interactions inside a community.
 */
interface FollowService
{
    public function followMember(CommunityMember $follower, CommunityMember $followed): CommunityFollow;

    public function unfollowMember(CommunityMember $follower, CommunityMember $followed): void;

    public function toggleFollow(CommunityMember $follower, CommunityMember $followed): CommunityFollow;

    public function getFollowers(CommunityMember $member): Collection;

    public function getFollowing(CommunityMember $member): Collection;

    public function syncExternalFollowers(User $user, CommunityMember $member): void;
}
