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

        Gate::before(function (User $user) use ($matrix) {
            return $matrix->allows($user, '*');
        });

        Gate::define('community.view', function (User $user, mixed $community = null) use ($matrix) {
            $context = ['public' => false];

            if (is_bool($community)) {
                $context['public'] = $community;
            } elseif (is_object($community) && method_exists($community, 'getAttribute')) {
                $context['target'] = $community;
                $context['public'] = $community->getAttribute('visibility') === 'public';
            }

            return $matrix->allows($user, 'community.view', $context);
        });

        Gate::define('community.post', function (User $user, mixed $community = null) use ($matrix) {
            $context = [];
            if (is_object($community)) {
                $context['target'] = $community;
            }

            return $matrix->allows($user, 'community.post', $context);
        });

        Gate::define('community.moderate', function (User $user, mixed $community = null) use ($matrix) {
            $context = [];
            if (is_object($community)) {
                $context['target'] = $community;
            }

            return $matrix->allows($user, 'community.moderate', $context);
        });

        Gate::define('post.update', function (User $user, mixed $post = null) use ($matrix) {
            $context = ['is_owner' => false];
            if (is_object($post) && method_exists($post, 'getAttribute')) {
                $context['target'] = $post;
                $context['is_owner'] = (int) $post->getAttribute('user_id') === (int) $user->getKey()
                    || (int) $post->getAttribute('author_id') === (int) $user->getKey();
            }

            return $matrix->allows($user, 'post.update', $context);
        });

        Gate::define('post.pin', function (User $user, mixed $post = null) use ($matrix) {
            $context = [];
            if (is_object($post)) {
                $context['target'] = $post;
            }

            return $matrix->allows($user, 'post.pin', $context);
        });

        Gate::define('member.ban', function (User $user, mixed $community = null) use ($matrix) {
            $context = [];
            if (is_object($community)) {
                $context['target'] = $community;
            }

            return $matrix->allows($user, 'member.ban', $context);
        });

        Gate::define('paywall.manage', function (User $user, mixed $community = null) use ($matrix) {
            $context = [];
            if (is_object($community)) {
                $context['target'] = $community;
            }

            return $matrix->allows($user, 'paywall.manage', $context);
        });
    }
}
