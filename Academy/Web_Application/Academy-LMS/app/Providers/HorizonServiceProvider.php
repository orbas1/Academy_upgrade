<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Horizon::auth(function (Request $request): bool {
            if (app()->environment('local')) {
                return true;
            }

            $user = $request->user();

            return $user !== null && $user->role === 'admin';
        });

        if ($email = config('mail.horizon_notifications_to')) {
            Horizon::routeMailNotificationsTo($email);
        }

        if ($slackWebhook = config('services.slack.horizon_webhook')) {
            Horizon::routeSlackNotificationsTo($slackWebhook);
        }
    }
}
