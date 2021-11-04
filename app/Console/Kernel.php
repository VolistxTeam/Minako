<?php

namespace App\Console;

use Illuminate\Console\KeyGenerateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Laravelista\LumenVendorPublish\VendorPublishCommand;
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
        VendorPublishCommand::class,
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

        $schedule->command('minako:notify:anime')->sundays()->runInBackground();
        $schedule->command('minako:notify:characters')->sundays()->runInBackground();
        $schedule->command('minako:notify:company')->sundays()->runInBackground();
        $schedule->command('minako:notify:relation')->sundays()->runInBackground();
        $schedule->command('minako:notify:character-relation')->sundays()->runInBackground();
        $schedule->command('minako:notify:thumbnail')->sundays()->runInBackground();
        $schedule->command('minako:notify:character-image')->sundays()->runInBackground();

        $schedule->command('minako:mal:episodes')->sundays()->runInBackground();
    }
}
