<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ScheduleListCommand::class,
        Commands\MAL\EpisodeCommand::class,
        Commands\Notify\AnimeCommand::class,
        Commands\Notify\CharacterCommand::class,
        Commands\Notify\ThumbnailCommand::class,
        Commands\Notify\CharacterRelationCommand::class,
        Commands\Notify\CompanyCommand::class,
        Commands\Notify\RelationCommand::class,
        Commands\Notify\ThumbnailCommand::class,
        Commands\Ohys\DownloadCommand::class,
        Commands\Ohys\RelationCommand::class,
        Commands\Ohys\RecreateCommand::class,
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

        $schedule->command('minako:ohys:download')->hourly()->runInBackground()->withoutOverlapping();

        $schedule->command('minako:notify:anime')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:characters')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:companies')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:relations')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:character-relations')->saturdays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:thumbnails')->sundays()->at('00:00')->runInBackground()->withoutOverlapping();
        $schedule->command('minako:notify:character-images')->sundays()->at('00:00')->runInBackground()->withoutOverlapping();

        $schedule->command('minako:mal:episodes --skip=13000')->weekly()->days([1, 4])->at('00:00')->runInBackground()->withoutOverlapping();
    }
}
