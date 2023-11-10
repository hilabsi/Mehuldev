<?php

namespace App\Console;

use App\Console\Commands\ServCommand;
use App\Console\Commands\VendorPublishCommand;
use App\Modules\Partner\Jobs\CreateWeeklyInvoices;
use App\Modules\Trip\Jobs\RunScheduledTrips;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ServCommand::class,
        VendorPublishCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new RunScheduledTrips())->name('running_scheduled_trips')->withoutOverlapping();
        $schedule->job(new CreateWeeklyInvoices())->name('running_weekly_invoices')->withoutOverlapping()->weekly();
    }
}
