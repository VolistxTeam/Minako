<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyThumbnailJob;
use App\Models\NotifyAnime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ThumbnailCommand extends Command
{
    protected $signature = 'minako:notify:character-images';

    protected $description = 'Download character images from notify.moe.';

    public function handle()
    {
        $this->setUnlimitedTimeLimit();
        $this->createCharacterDirectoryIfNotExists();

        $allAnime = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID', 'image_extension')->get()->toArray();

        $this->processAllAnime($allAnime);
    }

    private function setUnlimitedTimeLimit()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);
    }

    private function createCharacterDirectoryIfNotExists()
    {
        if (!Storage::disk('local')->exists('posters')) {
            Storage::disk('local')->makeDirectory('posters');
        }
    }

    private function processAllAnime($allAnime)
    {
        $totalCount = count($allAnime);

        $this->info(PHP_EOL.'[!] Querying for Work...'.PHP_EOL);

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        foreach ($allAnime as $item) {
            if (!empty($item['image_extension'])) {
                if (Storage::disk('local')->exists('posters/'.$item['uniqueID'].'.jpg')) {
                    continue;
                }

                $this->processAnimeItem($item);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
    }

    private function processAnimeItem($item)
    {
        try {
            dispatch(new NotifyThumbnailJob($item['id'], $item['notifyID'], $item['uniqueID'], $item['image_extension']));
        } catch (Exception $ex) {
            // If there is an exception, continue with the next item
        }
    }
}
