<?php

namespace App\Support\Caching\Warmers;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Throwable;

class NavigationCacheWarmer implements CacheWarmer
{
    public function warm(): void
    {
        try {
            Category::query()
                ->with(['childs' => static function ($query): void {
                    $query->orderBy('id');
                }])
                ->orderBy('id')
                ->remember(now()->addMinutes(60), 'navigation:categories', ['navigation', 'categories']);
        } catch (Throwable $exception) {
            Log::warning('Failed to warm navigation cache', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
