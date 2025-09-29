<?php

declare(strict_types=1);

namespace App\Policies\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPostPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, CommunityPost $post): bool
    {
        if ($post->visibility === 'public') {
            return true;
        }

        return $user !== null && $post->community->members()->where('user_id', $user->getKey())->exists();
    }

    public function update(User $user, CommunityPost $post): bool
    {
        $membership = $this->membershipFor($user, $post->community);

        if ($post->author_id === $user->getKey()) {
            return true;
        }

        return $this->hasModeratorRole($membership);
    }

    public function delete(User $user, CommunityPost $post): bool
    {
        $membership = $this->membershipFor($user, $post->community);

        return $post->author_id === $user->getKey() || $this->hasModeratorRole($membership);
    }

    public function pin(User $user, CommunityPost $post): bool
    {
        return $this->hasModeratorRole($this->membershipFor($user, $post->community));
    }

    public function lock(User $user, CommunityPost $post): bool
    {
        return $this->hasModeratorRole($this->membershipFor($user, $post->community));
    }

    private function membershipFor(User $user, Community $community): ?CommunityMember
    {
        return $community->members()->where('user_id', $user->getKey())->first();
    }

    private function hasModeratorRole(?CommunityMember $member): bool
    {
        if (! $member || ! $member->role) {
            return false;
        }

        return in_array(
            CommunityMemberRole::from($member->role),
            [CommunityMemberRole::MODERATOR, CommunityMemberRole::ADMIN, CommunityMemberRole::OWNER],
            true
        );
    }
}
