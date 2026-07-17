<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Parent/child sync — see config/parent_sync.php. Every 5 minutes,
        // not 1, given uncertain cron reliability on shared hosting; both
        // are safe no-ops when PARENT_SYNC_ENABLED is unset/false.
        $schedule->command('parent-sync:push')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('parent-sync:pull-directives')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('parent-sync:pull-credit-events')->everyFiveMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
