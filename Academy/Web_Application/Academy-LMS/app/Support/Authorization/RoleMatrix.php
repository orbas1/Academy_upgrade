<?php

namespace App\Support\Authorization;

use App\Models\User;

class RoleMatrix
{
    /**
     * Map application roles to authorization abilities.
     */
    private array $abilities = [
        'guest' => [
            'community.view',
        ],
        'student' => [
            'community.view',
            'community.post',
            'post.update',
        ],
        'instructor' => [
            'community.view',
            'community.post',
            'community.moderate',
            'post.update',
            'post.pin',
        ],
        'admin' => [
            'community.view',
            'community.post',
            'community.moderate',
            'post.update',
            'post.pin',
            'member.ban',
            'paywall.manage',
        ],
    ];

    public function allows(User $user, string $ability): bool
    {
        $role = $user->role ?? 'guest';
        $abilities = $this->abilities[$role] ?? $this->abilities['guest'];

        return in_array($ability, $abilities, true);
    }
}
