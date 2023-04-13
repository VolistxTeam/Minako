<?php

namespace App\Console\Commands\Ohys;

use App\Jobs\OhysRelationJob;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use Illuminate\Console\Command;

class RelationCommand extends Command
{
    protected $signature = 'minako:ohys:relations';

    protected $description = 'Search and save relationships for ohys torrents.';

    public function handle()
    {
        $this->setUnlimitedTimeLimit();

        $allTorrents = OhysTorrent::query()->orderByDesc('updated_at')->cursor();

        $totalCount = $allTorrents->count();

        $this->info(PHP_EOL.'[!] Querying for Work...'.PHP_EOL);

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        foreach ($allTorrents as $torrent) {
            if (!$this->relationExists($torrent->uniqueID)) {
                dispatch(new OhysRelationJob($torrent));
            }
            $progressBar->advance();
        }

        $progressBar->finish();

        return 0;
    }

    private function setUnlimitedTimeLimit()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);
    }

    private function relationExists(string $uniqueID): bool
    {
        return OhysRelation::query()->where('uniqueID', $uniqueID)->exists();
    }
}
