<?php

declare(strict_types=1);

namespace App\Policies\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Models\Community\Community;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPaywallPolicy
{
    use HandlesAuthorization;

    public function manage(User $user, Community $community): bool
    {
        $membership = $community->members()->where('user_id', $user->getKey())->first();

        if (! $membership || ! $membership->role) {
            return false;
        }

        return in_array(
            CommunityMemberRole::from($membership->role),
            [CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN],
            true
        );
    }

    public function view(User $user, Community $community): bool
    {
        return $community->members()->where('user_id', $user->getKey())->exists();
    }
}
