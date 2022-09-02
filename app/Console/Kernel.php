<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Monicahq\Cloudflare\Commands\Reload;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Reload::class,
        ScheduleListCommand::class,
        Commands\MAL\EpisodeCommand::class,
        Commands\Notify\AnimeCommand::class,
        Commands\Notify\CharacterCommand::class,
        Commands\Notify\CharacterImageCommand::class,
        Commands\Notify\CharacterRelationCommand::class,
        Commands\Notify\CompanyCommand::class,
        Commands\Notify\RelationCommand::class,
        Commands\Notify\ThumbnailCommand::class,
        Commands\Ohys\DownloadCommand::class,
        Commands\Ohys\RelationCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        set_time_limit(0);

        $schedule->command('minako:ohys:download')->hourly()->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:ohys:relation')->everyThreeHours()->runInBackground()->withoutOverlapping(5000);

        $schedule->command('minako:notify:anime')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:characters')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:company')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:relation')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:character-relation')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:thumbnail')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping(5000);
        $schedule->command('minako:notify:character-image')->sundays()->at('00:00')->runInBackground()->withoutOverlapping(5000);

        $schedule->command('minako:mal:episodes')->weekly()->days([1, 4])->at('00:00')->runInBackground()->withoutOverlapping(10000);
    }
}
