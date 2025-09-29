<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullFollowService implements FollowService
{
    use NotImplemented;
    public function followMember(\App\Models\Community\CommunityMember $follower, \App\Models\Community\CommunityMember $followed): \App\Models\Community\CommunityFollow
    {
        $this->notImplemented();
    }

    public function unfollowMember(\App\Models\Community\CommunityMember $follower, \App\Models\Community\CommunityMember $followed): void
    {
        $this->notImplemented();
    }

    public function toggleFollow(\App\Models\Community\CommunityMember $follower, \App\Models\Community\CommunityMember $followed): \App\Models\Community\CommunityFollow
    {
        $this->notImplemented();
    }

    public function getFollowers(\App\Models\Community\CommunityMember $member): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function getFollowing(\App\Models\Community\CommunityMember $member): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function syncExternalFollowers(\App\Models\User $user, \App\Models\Community\CommunityMember $member): void
    {
        $this->notImplemented();
    }
}
