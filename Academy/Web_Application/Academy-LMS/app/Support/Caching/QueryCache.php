<?php

namespace App\Support\Caching;

use DateInterval;
use DateTimeInterface;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepositoryContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QueryCache
{
    public static function remember(QueryBuilder|EloquentBuilder $builder, $ttl = null, ?string $key = null, ?array $tags = null)
    {
        $cache = self::resolveCacheStore();

        if (! self::shouldCache($cache)) {
            return $builder->get();
        }

        $cacheKey = $key ?? self::generateCacheKey($builder, 'collection');
        $ttlValue = self::normalizeTtl($ttl);
        $cacheInstance = self::resolveTaggedStore($cache, $builder, $tags);

        return $cacheInstance->remember($cacheKey, $ttlValue, static function () use ($builder) {
            return $builder->get();
        });
    }

    public static function rememberFirst(QueryBuilder|EloquentBuilder $builder, $ttl = null, ?string $key = null, ?array $tags = null)
    {
        $cache = self::resolveCacheStore();

        if (! self::shouldCache($cache)) {
            return $builder->first();
        }

        $cacheKey = $key ?? self::generateCacheKey($builder, 'first');
        $ttlValue = self::normalizeTtl($ttl);
        $cacheInstance = self::resolveTaggedStore($cache, $builder, $tags);

        return $cacheInstance->remember($cacheKey, $ttlValue, static function () use ($builder) {
            return $builder->first();
        });
    }

    public static function rememberCount(QueryBuilder|EloquentBuilder $builder, $ttl = null, ?string $key = null, ?array $tags = null): int
    {
        $cache = self::resolveCacheStore();

        if (! self::shouldCache($cache)) {
            return (int) $builder->count();
        }

        $cacheKey = $key ?? self::generateCacheKey($builder, 'count');
        $ttlValue = self::normalizeTtl($ttl);
        $cacheInstance = self::resolveTaggedStore($cache, $builder, $tags);

        return (int) $cacheInstance->remember($cacheKey, $ttlValue, static function () use ($builder) {
            return $builder->count();
        });
    }

    protected static function resolveCacheStore(): CacheRepositoryContract
    {
        $store = config('performance.query_cache.store');

        if ($store) {
            return Cache::store($store);
        }

        return Cache::store();
    }

    protected static function shouldCache(CacheRepositoryContract $cache): bool
    {
        if (! config('performance.query_cache.enabled', false)) {
            return false;
        }

        if (config('app.debug') && ! config('performance.query_cache.allow_on_debug', false)) {
            return false;
        }

        if (! method_exists($cache, 'getStore')) {
            return true;
        }

        return true;
    }

    protected static function resolveTaggedStore(CacheRepositoryContract $cache, QueryBuilder|EloquentBuilder $builder, ?array $tags = null): CacheRepositoryContract|Repository
    {
        $resolvedTags = self::resolveTags($builder, $tags);

        if (empty($resolvedTags) || ! method_exists($cache, 'supportsTags') || ! $cache->supportsTags()) {
            return $cache;
        }

        return $cache->tags($resolvedTags);
    }

    protected static function resolveTags(QueryBuilder|EloquentBuilder $builder, ?array $tags = null): array
    {
        if ($tags !== null) {
            return array_values(array_unique(array_filter($tags)));
        }

        if ($builder instanceof EloquentBuilder) {
            $model = $builder->getModel();
            $class = $model::class;

            return ['model:' . Str::slug(class_basename($class))];
        }

        return [];
    }

    protected static function normalizeTtl($ttl): int|DateInterval|DateTimeInterface
    {
        if ($ttl instanceof DateInterval || $ttl instanceof DateTimeInterface) {
            return $ttl;
        }

        if ($ttl === null) {
            return (int) max(config('performance.query_cache.default_ttl', 300), 1);
        }

        if (is_numeric($ttl)) {
            return (int) max((int) $ttl, 1);
        }

        throw new InvalidArgumentException('Cache TTL must be null, numeric, DateInterval, or DateTimeInterface.');
    }

    protected static function generateCacheKey(QueryBuilder|EloquentBuilder $builder, string $suffix): string
    {
        $sql = $builder->toSql();
        $bindings = $builder->getBindings();
        $connection = $builder->getConnection()->getName();

        return sprintf('qc:%s:%s:%s', $connection, $suffix, sha1($sql . '|' . serialize($bindings)));
    }
}
