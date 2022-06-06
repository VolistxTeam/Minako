<?php

namespace App\Console;

use App\Console\Commands\TestCommand;
use Illuminate\Console\Scheduling\Schedule;
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
        TestCommand::class
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

        $schedule->command('minako:ohys:download')->hourly()->runInBackground();
        $schedule->command('minako:ohys:relation')->everyThreeHours()->runInBackground();

        // $schedule->command('minako:notify:anime')->mondays();
        // $schedule->command('minako:notify:characters')->tuesdays();
        // $schedule->command('minako:notify:company')->wednesdays();
        // $schedule->command('minako:notify:relation')->thursdays();
        // $schedule->command('minako:notify:character-relation')->fridays();
        // $schedule->command('minako:notify:thumbnail')->saturdays();
        // $schedule->command('minako:notify:character-image')->sundays();

        // $schedule->command('minako:mal:episodes')->weekly()->days([1, 4])->at('00:00')->runInBackground();
    }
}
