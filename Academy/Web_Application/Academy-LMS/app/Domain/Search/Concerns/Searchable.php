<?php

namespace App\Domain\Search\Concerns;

use App\Domain\Search\SearchSyncManager;
use Illuminate\Database\Eloquent\Model;

trait Searchable
{
    public static function bootSearchable(): void
    {
        static::saved(function (Model $model) {
            app(SearchSyncManager::class)->queueModelSync($model);
        });

        static::deleted(function (Model $model) {
            app(SearchSyncManager::class)->queueModelDeletion($model);
        });
    }
}
