<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\GenerateInvoicesJob;
use App\Jobs\CheckOverdueInvoicesJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate invoices on the 1st of every month at 00:00
        $schedule->job(new GenerateInvoicesJob())
            ->monthlyOn(1, '00:00')
            ->timezone('Asia/Jakarta');

        // Check overdue invoices daily at 01:00
        $schedule->job(new CheckOverdueInvoicesJob())
            ->dailyAt('01:00')
            ->timezone('Asia/Jakarta');
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
