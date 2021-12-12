<?php

namespace App\Console;

use Illuminate\Console\KeyGenerateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Mlntn\Console\Commands\Serve;
use Spatie\ResponseCache\Commands\ClearCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ClearCommand::class,
        KeyGenerateCommand::class,
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
        Serve::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        set_time_limit(0);

        $schedule->command('minako:ohys:download')->hourly()->runInBackground();

        $schedule->command('minako:ohys:relation')->everyThreeHours()->runInBackground();

        $schedule->command('minako:notify:anime')->sundays();
        $schedule->command('minako:notify:characters')->sundays();
        $schedule->command('minako:notify:company')->sundays();
        $schedule->command('minako:notify:relation')->sundays();
        $schedule->command('minako:notify:character-relation')->sundays();
        $schedule->command('minako:notify:thumbnail')->sundays();
        $schedule->command('minako:notify:character-image')->sundays();

        $schedule->command('minako:mal:episodes')->weekly()->days([1, 4])->at('00:00')->runInBackground();
    }
}
