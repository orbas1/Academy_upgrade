<?php

namespace App\Providers;

use App\Services\Queue\QueueMetricsFetcher;
use App\Services\Queue\QueueThresholdEvaluator;
use App\Services\Queue\RedisQueueMetricsFetcher;
use App\Services\Security\UploadSecurityService;
use App\Support\Authorization\RoleMatrix;
use App\Support\Database\KeysetPaginator;
use App\Support\Database\MySqlPerformanceConfigurator;
use App\Support\FeatureFlags\FeatureRolloutRepository;
use App\Support\Http\ApiResponseBuilder;
use App\Support\Localization\LocaleManager;
use App\Support\Migrations\MigrationPlanner;
use App\Support\Migrations\MigrationRunbookRegistry;
use App\Support\Security\TwoFactorAuthenticator;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        $this->app->singleton(QueueMetricsFetcher::class, function ($app) {
            return new RedisQueueMetricsFetcher($app->make(\Illuminate\Contracts\Redis\Factory::class));
        });

        $this->app->singleton(QueueThresholdEvaluator::class, function ($app) {
            return new QueueThresholdEvaluator($app->make(ConfigRepository::class));
        });

        $this->app->singleton(MigrationPlanner::class, function ($app) {
            $config = $app->make(ConfigRepository::class)->get('migrations', []);

            return new MigrationPlanner(is_array($config) ? $config : []);
        });

        $this->app->singleton(MigrationRunbookRegistry::class, function ($app) {
            $config = $app->make(ConfigRepository::class)->get('migration_runbooks', []);

            return new MigrationRunbookRegistry(is_array($config) ? $config : []);
        });

        $this->app->scoped(ApiResponseBuilder::class, function ($app) {
            return new ApiResponseBuilder(
                (string) Str::orderedUuid(),
                $app['config']->get('app.timezone', 'UTC')
            );
        });

        $this->app->singleton(LocaleManager::class, function ($app) {
            return new LocaleManager($app->make(ConfigRepository::class));
        });

        $this->app->singleton(FeatureRolloutRepository::class, function ($app) {
            return new FeatureRolloutRepository(
                $app->make(Filesystem::class),
                storage_path('app/feature-rollouts.json')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        KeysetPaginator::register();
        MySqlPerformanceConfigurator::applyFromConfig();

        $localeManager = $this->app->make(LocaleManager::class);

        View::composer('*', function ($view) use ($localeManager) {
            $current = $localeManager->current();

            $view->with('currentLocale', $current);
            $view->with('supportedLocales', $localeManager->supported());
            $view->with('localeDirection', $localeManager->direction($current['code']));
        });
    }
}
