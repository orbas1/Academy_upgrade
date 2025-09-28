<?php

namespace App\Support\Octane\Listeners;

use Illuminate\Contracts\Foundation\Application;

class FlushScopedInstances
{
    public function __construct(private readonly Application $app)
    {
    }

    public function __invoke(object $event): void
    {
        if (! $this->app['config']->get('octane.leak_guard.enabled', true)) {
            return;
        }

        $this->app->forgetScopedInstances();
    }
}
