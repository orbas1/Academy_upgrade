<?php

namespace App\Providers;

use App\Support\Security\TwoFactorAuthenticator;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
    }
}
