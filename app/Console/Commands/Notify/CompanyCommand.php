<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyCompanyJob;
use App\Models\NotifyAnime;
use Illuminate\Console\Command;

class CompanyCommand extends Command
{
    protected $signature = 'minako:notify:company';

    protected $description = 'Retrieve all company information from notify.moe.';

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);

        $allAnime = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID', 'studios', 'producers', 'licensors')->get()->toArray();

        $this->info('[!] Starting the process to crawl information...');

        $totalCount = count($allAnime);

        $this->info(PHP_EOL . '[!] Querying for Work...' . PHP_EOL);

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        foreach ($allAnime as $item) {
            dispatch(new NotifyCompanyJob($item));

            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
