<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider as LaravelDuskServiceProvider;

class DuskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local', 'testing', 'dusk.ci')) {
            $this->app->register(LaravelDuskServiceProvider::class);
        }
    }
}
