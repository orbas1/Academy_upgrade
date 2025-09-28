<?php

namespace App\Support\Caching\Warmers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SettingsCacheWarmer implements CacheWarmer
{
    public function warm(): void
    {
        try {
            $settings = Setting::query()
                ->select(['type', 'description'])
                ->remember(now()->addMinutes(30), 'settings:all', ['settings']);

            Cache::put('settings:map', $settings->keyBy('type'), now()->addMinutes(30));
        } catch (Throwable $exception) {
            Log::warning('Failed to warm settings cache', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
