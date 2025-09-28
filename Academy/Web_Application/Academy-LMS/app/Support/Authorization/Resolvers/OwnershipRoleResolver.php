<?php

namespace App\Support\Authorization\Resolvers;

use App\Models\User;
use App\Support\Authorization\Contracts\RoleResolver;
use Illuminate\Support\Arr;

class OwnershipRoleResolver implements RoleResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function resolve(User $user, array $context = []): array
    {
        $roles = [];

        $target = Arr::get($context, 'target');
        if ($target && method_exists($target, 'getAttribute')) {
            $ownerId = $target->getAttribute('user_id') ?? $target->getAttribute('author_id') ?? $target->getAttribute('owner_id');
            if ($ownerId && (int) $ownerId === (int) $user->getKey()) {
                $roles[] = 'owner';
            }
        }

        if (Arr::get($context, 'owner_id') && (int) Arr::get($context, 'owner_id') === (int) $user->getKey()) {
            $roles[] = 'owner';
        }

        return $roles;
    }
}
