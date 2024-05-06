<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyPosterJob;
use App\Models\NotifyAnime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\progress;

class PosterCommand extends Command
{
    protected $signature = 'minako:notify:poster';

    protected $description = 'Download anime poster images from media.notify.moe.';

    public function handle(): void
    {
        $this->setUnlimitedTimeLimit();
        $this->createCharacterDirectoryIfNotExists();

        $posters = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID', 'image_extension')->cursor();

        $this->processAllAnime($posters);
    }

    private function setUnlimitedTimeLimit(): void
    {
        set_time_limit(0);
    }

    private function createCharacterDirectoryIfNotExists(): void
    {
        if (!Storage::disk('local')->exists('posters')) {
            Storage::disk('local')->makeDirectory('posters');
        }
    }

    private function processAllAnime($allAnime): void
    {
        $totalCount = count($allAnime);

        $progress = progress(label: 'Dispatching Jobs for Posters', steps: $totalCount);
        $progress->start();

        foreach ($allAnime as $item) {
            if (!empty($item['image_extension'])) {
                if (Storage::disk('local')->exists('posters/' . $item['uniqueID'] . '.jpg')) {
                    continue;
                }

                $this->processAnimeItem($item);
            }

            $progress->advance();
        }

        $progress->finish();
    }

    private function processAnimeItem($item): void
    {
        try {
            dispatch(new NotifyPosterJob($item['id'], $item['notifyID'], $item['uniqueID'], $item['image_extension']));
        } catch (Exception $ex) {
            // If there is an exception, continue with the next item
        }
    }
}
