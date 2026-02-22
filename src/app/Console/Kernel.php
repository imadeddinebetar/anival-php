<?php

namespace App\Console;

use Core\Console\Scheduling\Schedule;

class Kernel
{
    /**
     * Register application commands.
     *
     * @return array<int, class-string>
     */
    public function commands(): array
    {
        return [];
    }

    /**
     * Define the application's command schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }
}
