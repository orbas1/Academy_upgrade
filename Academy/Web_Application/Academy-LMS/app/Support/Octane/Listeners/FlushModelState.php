<?php

namespace App\Support\Octane\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;

class FlushModelState
{
    public function __construct(private readonly Application $app)
    {
    }

    public function __invoke(object $event): void
    {
        if (! $this->app['config']->get('octane.leak_guard.enabled', true) ||
            ! $this->app['config']->get('octane.leak_guard.model_flush', true)) {
            return;
        }

        Model::clearBootedModels();

        if (method_exists(Model::class, 'setEventDispatcher')) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = $this->app->make('events');
            Model::setEventDispatcher($dispatcher);
        }
    }
}
