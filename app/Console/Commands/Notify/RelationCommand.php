<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyRelationJob;
use App\Models\NotifyAnime;
use App\Models\NotifyCharacterRelation;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class RelationCommand extends Command
{
    protected $signature = 'minako:notify:relations';

    protected $description = 'Retrieve all character relation information from notify.moe.';

    public function handle()
    {
        $this->setUnlimitedTimeLimit();

        $allAnime = NotifyAnime::query()
            ->select('id', 'uniqueID', 'notifyID', 'studios', 'producers', 'licensors')
            ->cursor();

        $totalCount = NotifyAnime::query()->count();

        $this->info(PHP_EOL.'[!] Querying for Work...'.PHP_EOL);

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        foreach ($allAnime as $item) {
            try {
                if (!NotifyCharacterRelation::query()
                        ->select('notifyID', 'updated_at')
                        ->where('notifyID', $item->notifyID)
                        ->count() > 0) {
                    dispatch(new NotifyRelationJob($item->toArray()));
                }
            } catch (Exception $ex) {
                continue;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
    }

    private function setUnlimitedTimeLimit()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);
    }
}
