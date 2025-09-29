<?php

declare(strict_types=1);

namespace App\Policies\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityMemberPolicy
{
    use HandlesAuthorization;

    public function manage(User $user, CommunityMember $member): bool
    {
        $actorMembership = $this->membershipFor($user, $member->community);

        if (! $actorMembership || ! $actorMembership->role) {
            return false;
        }

        $actorRole = CommunityMemberRole::from($actorMembership->role);

        return in_array($actorRole, [CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN], true)
            || ($actorRole === CommunityMemberRole::MODERATOR && $member->role === CommunityMemberRole::MEMBER->value);
    }

    private function membershipFor(User $user, Community $community): ?CommunityMember
    {
        return $community->members()->where('user_id', $user->getKey())->first();
    }
}
