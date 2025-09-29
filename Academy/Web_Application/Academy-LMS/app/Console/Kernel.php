<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (config('performance.warmup.enabled', false)) {
            $schedule->command('app:cache-warm')
                ->dailyAt(config('performance.warmup.schedule', '02:00'))
                ->environments(['staging', 'production'])
                ->withoutOverlapping()
                ->runInBackground();
        }

        if (config('performance.query_cache.enabled', false)) {
            $schedule->command('cache:prune-stale-tags')->daily();
        }

        $schedule->command('horizon:snapshot')
            ->everyFiveMinutes()
            ->environments(['staging', 'production'])
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('queues:monitor')
            ->everyFiveMinutes()
            ->environments(['staging', 'production'])
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('communities:maintain --prune')
            ->dailyAt('01:30')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('communities:send-digest daily')
            ->dailyAt('07:00')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('communities:send-digest weekly')
            ->weeklyOn(1, '07:30')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
