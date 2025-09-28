<?php

namespace App\Support\Authorization\Resolvers;

use App\Models\User;
use App\Support\Authorization\Contracts\RoleResolver;
use Illuminate\Support\Arr;

class GlobalRoleResolver implements RoleResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function resolve(User $user, array $context = []): array
    {
        $role = $user->role ?? config('authorization.default_role', 'guest');

        return [Arr::get(config('authorization.aliases', []), $role, $role) ?: 'guest'];
    }
}
