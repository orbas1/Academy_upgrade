<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

class NullMembershipService implements MembershipService
{
    use NotImplemented;
    public function requestJoin(\App\Models\User $user, \App\Models\Community\Community $community, ?string $message = null): \App\Models\Community\CommunityMember
    {
        $this->notImplemented();
    }

    public function approveMember(\App\Models\Community\CommunityMember $member, \App\Models\User $actor): \App\Models\Community\CommunityMember
    {
        $this->notImplemented();
    }

    public function denyMember(\App\Models\Community\CommunityMember $member, \App\Models\User $actor, ?string $reason = null): void
    {
        $this->notImplemented();
    }

    public function leaveCommunity(\App\Models\Community\CommunityMember $member): void
    {
        $this->notImplemented();
    }

    public function banMember(\App\Models\Community\CommunityMember $member, \App\Models\User $actor, ?string $reason = null): void
    {
        $this->notImplemented();
    }

    public function unbanMember(\App\Models\Community\CommunityMember $member, \App\Models\User $actor): \App\Models\Community\CommunityMember
    {
        $this->notImplemented();
    }

    public function promoteMember(\App\Models\Community\CommunityMember $member, \App\Enums\Community\CommunityMemberRole $targetRole, \App\Models\User $actor): \App\Models\Community\CommunityMember
    {
        $this->notImplemented();
    }

    public function demoteMember(\App\Models\Community\CommunityMember $member, \App\Models\User $actor): \App\Models\Community\CommunityMember
    {
        $this->notImplemented();
    }

    public function recordLastSeen(\App\Models\Community\CommunityMember $member, \Carbon\CarbonInterface $seenAt): void
    {
        $this->notImplemented();
    }

    public function getActiveMembers(\App\Models\Community\Community $community, array $filters = []): LengthAwarePaginatorContract
    {
        $this->notImplemented();
    }
}
