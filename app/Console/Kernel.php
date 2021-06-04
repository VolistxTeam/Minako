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
        Commands\Ohys\ParseCommand::class,
        Commands\Ohys\RelationCommand::class,
        Serve::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        set_time_limit(0);

        $schedule->command('geoip:update')->monthly()->runInBackground();
        $schedule->command('minako:ohys:download')->hourly()->runInBackground();

        $schedule->command('minako:notify:anime')->daily();
        $schedule->command('minako:notify:characters')->daily();
        $schedule->command('minako:notify:company')->daily();
        $schedule->command('minako:notify:relation')->daily();
        $schedule->command('minako:notify:character-relation')->daily();
        $schedule->command('minako:notify:thumbnail')->daily();
        $schedule->command('minako:notify:character-image')->daily();

        $schedule->command('minako:mal:episodes')->daily();

        $schedule->command('responsecache:clear')->mondays();
    }
}
