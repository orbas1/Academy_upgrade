<?php

namespace App\Providers;

use App\Services\Security\UploadSecurityService;
use App\Support\Authorization\RoleMatrix;
use App\Support\Security\TwoFactorAuthenticator;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthenticator::class, function ($app) {
            $config = $app['config']->get('security.two_factor', []);

            $issuer = $config['issuer'] ?? $app['config']->get('app.name', 'Academy');
            $window = (int) ($config['window'] ?? 1);

            return new TwoFactorAuthenticator($issuer, max($window, 0));
        });

        $this->app->singleton(RoleMatrix::class, function ($app) {
            $config = $app['config']->get('authorization', []);
            $resolverClasses = $config['resolvers'] ?? [];
            $resolvers = array_map(static fn ($resolver) => $app->make($resolver), $resolverClasses);

            return new RoleMatrix(
                $config['matrix'] ?? [],
                $config['aliases'] ?? [],
                $config['default_role'] ?? 'guest',
                $resolvers
            );
        });

        $this->app->singleton(UploadSecurityService::class, function ($app) {
            return new UploadSecurityService(
                $app->make(ConfigRepository::class),
                $app->make(Filesystem::class),
                $app->make(BusDispatcher::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
    }
}
