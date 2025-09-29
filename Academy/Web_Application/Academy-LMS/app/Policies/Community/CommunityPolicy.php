<?php

declare(strict_types=1);

namespace App\Policies\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityVisibility;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, Community $community): bool
    {
        if ($community->visibility === CommunityVisibility::PUBLIC->value) {
            return true;
        }

        return $user !== null && $this->membershipFor($user, $community) !== null;
    }

    public function manage(User $user, Community $community): bool
    {
        $membership = $this->membershipFor($user, $community);

        return $this->hasRole($membership, CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN);
    }

    public function delete(User $user, Community $community): bool
    {
        $membership = $this->membershipFor($user, $community);

        return $this->hasRole($membership, CommunityMemberRole::OWNER);
    }

    private function membershipFor(User $user, Community $community): ?CommunityMember
    {
        return $community->members()->where('user_id', $user->getKey())->first();
    }

    private function hasRole(?CommunityMember $member, CommunityMemberRole ...$roles): bool
    {
        if (! $member || ! $member->role) {
            return false;
        }

        return in_array(CommunityMemberRole::from($member->role), $roles, true);
    }
}
