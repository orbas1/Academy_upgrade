<?php

namespace App\Providers;

use App\Console\Commands\SyncSearchConfiguration;
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

        $this->app->singleton(SearchClusterConfigurator::class, function ($app) {
            $config = $app['config']->get('search.meilisearch', []);
            $indexes = Arr::get($config, 'indexes', []);

            return new SearchClusterConfigurator(
                $app->make(MeilisearchClient::class),
                $indexes
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncSearchConfiguration::class,
            ]);
        }
    }
}
