<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyCharacterImageJob;
use App\Models\NotifyCharacter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\progress;

class CharacterImageCommand extends Command
{
    protected $signature = 'minako:notify:character-image';

    protected $description = 'Download character images from media.notify.moe.';

    public function handle(): void
    {
        $this->setUnlimitedTimeLimit();
        $this->createCharacterDirectoryIfNotExists();

        $characters = NotifyCharacter::query()->select('id', 'notifyID', 'uniqueID', 'image_extension')->cursor();

        $this->processAllAnime($characters);
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

        $progress = progress(label: 'Dispatching Jobs for Character Images', steps: $totalCount);
        $progress->start();

        foreach ($allAnime as $item) {
            if (!empty($item['image_extension'])) {
                if (Storage::disk('local')->exists('characters/'.$item['uniqueID'].'.jpg')) {
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
            dispatch(new NotifyCharacterImageJob($item['id'], $item['notifyID'], $item['uniqueID'], $item['image_extension']));
        } catch (Exception $ex) {
            // If there is an exception, continue with the next item
        }
    }
}
