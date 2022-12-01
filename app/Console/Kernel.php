<?php

namespace App\Console;

use App\Services\ResultService;
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

        $schedule->call(function () {
            ResultService::storeByApi();
        })->weeklyOn([2, 3, 6, 7], '18:40');

        $schedule->call(function () {
            ResultService::storeByApi();
        })->weeklyOn([2, 3, 6, 7], '19:10');

        $schedule->call(function () {
            ResultService::updateSpecialDraw();
        })->weeklyOn([7], '19:10');
        // ->weeklyOn([7], '11:30');

        // $schedule->call(function(){
        //     ResultService::storeByApi();
        // })->weeklyOn([3,6,7], '18:44');
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
