<?php

namespace App\Support\Octane\Listeners;

use Illuminate\Support\Facades\Auth;

class ResetAuthState
{
    public function __invoke(object $event): void
    {
        if (! config('octane.leak_guard.enabled', true) || ! config('octane.leak_guard.auth_reset', true)) {
            return;
        }

        Auth::forgetGuards();
    }
}
