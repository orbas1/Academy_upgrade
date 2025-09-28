<?php

namespace App\Support\Authorization\Contracts;

use App\Models\User;

interface RoleResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function resolve(User $user, array $context = []): array;
}
