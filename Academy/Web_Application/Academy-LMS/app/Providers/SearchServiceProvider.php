<?php

namespace App\Providers;

use App\Console\Commands\SearchIngestCommand;
use App\Console\Commands\SyncSearchConfiguration;
use App\Domain\Search\Services\SearchQueryService;
use App\Domain\Search\Services\SearchVisibilityService;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use App\Services\Search\MeilisearchClient;
use App\Services\Search\SearchClusterConfigurator;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MeilisearchClient::class, function ($app) {
            $config = $app['config']->get('search.meilisearch', []);

            return new MeilisearchClient(
                $config['host'] ?? 'http://meilisearch:7700',
                $config['key'] ?? null,
                (int) ($config['timeout'] ?? 10)
            );
        });

        $this->app->bind(SearchVisibilityService::class, function ($app) {
            return new SearchVisibilityService(
                tokenTtl: (int) $app['config']->get('search.visibility.ttl', 900)
            );
        });

        $this->app->bind(SearchVisibilityTokenService::class, function ($app) {
            return new SearchVisibilityTokenService(
                secret: $app['config']->get('search.visibility.token_secret')
            );
        });

        $this->app->singleton(SearchClusterConfigurator::class, function ($app) {
            $config = $app['config']->get('search.meilisearch', []);
            $indexes = Arr::get($config, 'indexes', []);

            return new SearchClusterConfigurator(
                $app->make(MeilisearchClient::class),
                $indexes
            );
        });

        $this->app->singleton(SearchQueryService::class, function ($app) {
            return new SearchQueryService(
                $app->make(MeilisearchClient::class),
                $app->make(SearchVisibilityService::class),
                $app->make(SearchVisibilityTokenService::class),
                $app['config']->get('search', [])
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SearchIngestCommand::class,
                SyncSearchConfiguration::class,
            ]);
        }
    }
}
