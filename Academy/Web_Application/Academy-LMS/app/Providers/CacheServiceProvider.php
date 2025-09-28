<?php

namespace App\Providers;

use App\Support\Caching\QueryCache;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerQueryBuilderMacros();
    }

    protected function registerQueryBuilderMacros(): void
    {
        if (! EloquentBuilder::hasGlobalMacro('remember')) {
            EloquentBuilder::macro('remember', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::remember($this, $ttl, $key, $tags);
            });
        }

        if (! EloquentBuilder::hasGlobalMacro('rememberFirst')) {
            EloquentBuilder::macro('rememberFirst', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::rememberFirst($this, $ttl, $key, $tags);
            });
        }

        if (! EloquentBuilder::hasGlobalMacro('rememberCount')) {
            EloquentBuilder::macro('rememberCount', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::rememberCount($this, $ttl, $key, $tags);
            });
        }

        if (! QueryBuilder::hasMacro('remember')) {
            QueryBuilder::macro('remember', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::remember($this, $ttl, $key, $tags);
            });
        }

        if (! QueryBuilder::hasMacro('rememberFirst')) {
            QueryBuilder::macro('rememberFirst', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::rememberFirst($this, $ttl, $key, $tags);
            });
        }

        if (! QueryBuilder::hasMacro('rememberCount')) {
            QueryBuilder::macro('rememberCount', function ($ttl = null, ?string $key = null, ?array $tags = null) {
                return QueryCache::rememberCount($this, $ttl, $key, $tags);
            });
        }
    }
}


