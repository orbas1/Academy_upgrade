<?php

namespace App\Http\Middleware;

use App\Support\Authorization\RoleMatrix;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function __construct(private readonly RoleMatrix $roles)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  $guards
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $required = collect($roles)
            ->flatMap(fn ($role) => preg_split('/[|,]/', $role) ?: [])
            ->map(fn ($role) => trim($role))
            ->filter()
            ->map(fn ($role) => $this->normalizeRole($role))
            ->unique()
            ->values();

        if ($required->isEmpty()) {
            return $next($request);
        }

        $resolved = collect($this->roles->resolveRoles($user));

        if ($resolved->intersect($required)->isEmpty()) {
            abort(403);
        }

        return $next($request);
    }

    private function normalizeRole(string $role): string
    {
        $aliases = config('authorization.aliases', []);
        $role = strtolower($role);

        return $aliases[$role] ?? $role;
    }
}
