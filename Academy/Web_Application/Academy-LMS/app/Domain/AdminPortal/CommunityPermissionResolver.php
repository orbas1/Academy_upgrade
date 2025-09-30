<?php

namespace App\Domain\AdminPortal;

use App\Models\User;

class CommunityPermissionResolver
{
    public function resolve(User $user): array
    {
        return match ($user->role) {
            'admin' => [
                'communities.manage',
                'communities.moderate',
            ],
            'instructor' => [
                'communities.manage',
            ],
            default => [],
        };
    }
}
