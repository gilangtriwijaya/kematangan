<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SsoSyncUsers::class,
        \App\Console\Commands\SsoReconcileOpdMappings::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run SSO sync every 12 hours (cron at minute 0, every 12th hour)
        $schedule->command('sso:sync-users --all')->cron('0 */12 * * *');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
