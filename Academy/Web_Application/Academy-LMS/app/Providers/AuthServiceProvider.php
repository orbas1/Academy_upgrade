<?php

namespace App\Providers;

use App\Support\Authorization\RoleMatrix;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $matrix = $this->app->make(RoleMatrix::class);

        Gate::before(function (User $user) {
            return $user->role === 'admin' ? true : null;
        });

        Gate::define('community.view', fn (User $user) => $matrix->allows($user, 'community.view'));
        Gate::define('community.post', fn (User $user) => $matrix->allows($user, 'community.post'));
        Gate::define('community.moderate', fn (User $user) => $matrix->allows($user, 'community.moderate'));
        Gate::define('post.update', fn (User $user) => $matrix->allows($user, 'post.update'));
        Gate::define('post.pin', fn (User $user) => $matrix->allows($user, 'post.pin'));
        Gate::define('member.ban', fn (User $user) => $matrix->allows($user, 'member.ban'));
        Gate::define('paywall.manage', fn (User $user) => $matrix->allows($user, 'paywall.manage'));
    }
}
