<?php

namespace App\Support\Octane\Listeners;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

class ResetTranslatorState
{
    public function __construct(private readonly Application $app)
    {
    }

    public function __invoke(object $event): void
    {
        if (! $this->app['config']->get('octane.leak_guard.enabled', true) ||
            ! $this->app['config']->get('octane.leak_guard.translator_reset', true)) {
            return;
        }

        if (! $this->app->bound('translator')) {
            return;
        }

        /** @var Translator $translator */
        $translator = $this->app->make('translator');

        if (method_exists($translator, 'setLocale')) {
            $translator->setLocale($this->app['config']->get('app.locale'));
        }

        if (method_exists($translator, 'setFallback')) {
            $translator->setFallback($this->app['config']->get('app.fallback_locale'));
        }
    }
}
