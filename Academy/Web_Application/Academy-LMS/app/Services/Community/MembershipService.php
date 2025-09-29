<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Contract describing how membership lifecycle actions are orchestrated.
 */
interface MembershipService
{
    public function requestJoin(User $user, Community $community, ?string $message = null): CommunityMember;

    public function approveMember(CommunityMember $member, User $actor): CommunityMember;

    public function denyMember(CommunityMember $member, User $actor, ?string $reason = null): void;

    public function leaveCommunity(CommunityMember $member): void;

    public function banMember(CommunityMember $member, User $actor, ?string $reason = null): void;

    public function unbanMember(CommunityMember $member, User $actor): CommunityMember;

    public function promoteMember(CommunityMember $member, CommunityMemberRole $targetRole, User $actor): CommunityMember;

    public function demoteMember(CommunityMember $member, User $actor): CommunityMember;

    public function recordLastSeen(CommunityMember $member, CarbonInterface $seenAt): void;

    public function getActiveMembers(Community $community, array $filters = []): Collection;
}
