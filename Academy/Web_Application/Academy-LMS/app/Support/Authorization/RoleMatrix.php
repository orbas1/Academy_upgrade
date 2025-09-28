<?php

namespace App\Support\Authorization;

use App\Models\User;
use App\Support\Authorization\Contracts\RoleResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RoleMatrix
{
    /** @var array<int, RoleResolver> */
    private array $resolvers;

    /**
     * @param  iterable<int, RoleResolver>  $resolvers
     */
    public function __construct(private readonly array $matrix, private readonly array $aliases, private readonly string $defaultRole, iterable $resolvers = [])
    {
        $this->resolvers = collect($resolvers)->filter(static fn ($resolver) => $resolver instanceof RoleResolver)->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function allows(User $user, string $ability, array $context = []): bool
    {
        $roles = $this->resolveRoles($user, $context);

        foreach ($roles as $role) {
            $permissions = $this->matrix[$role] ?? [];

            if (in_array('*', $permissions, true)) {
                return true;
            }

            foreach ($permissions as $permission) {
                if ($this->matches($permission, $ability, $context)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function resolveRoles(User $user, array $context = []): array
    {
        $roles = Collection::make();
        $roles->push($this->aliasFor($user->role));

        foreach ($this->resolvers as $resolver) {
            $roles->push(...$resolver->resolve($user, $context));
        }

        if ($contextRoles = Arr::get($context, 'roles')) {
            $roles->push(...Arr::wrap($contextRoles));
        }

        return $roles
            ->filter()
            ->map(fn (string $role) => $this->aliasFor($role))
            ->unique()
            ->values()
            ->all();
    }

    private function aliasFor(?string $role): string
    {
        $role = $role ?: $this->defaultRole;

        return $this->aliases[$role] ?? $role ?? $this->defaultRole;
    }

    private function matches(string $permission, string $ability, array $context = []): bool
    {
        if ($permission === $ability) {
            return true;
        }

        if (str_contains($permission, ':')) {
            [$name, $qualifier] = explode(':', $permission, 2);
            if ($name !== $ability) {
                return false;
            }

            return match ($qualifier) {
                'public' => (bool) Arr::get($context, 'public', false),
                'own' => (bool) Arr::get($context, 'is_owner', false),
                default => false,
            };
        }

        if (str_ends_with($permission, '.*')) {
            $prefix = rtrim($permission, '.*');

            return str_starts_with($ability, $prefix);
        }

        return false;
    }
}
