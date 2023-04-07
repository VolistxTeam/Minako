<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyCharacterImageJob;
use App\Models\NotifyCharacter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CharacterImageCommand extends Command
{
    protected $signature = 'minako:notify:character-image';

    protected $description = 'Download character images from notify.moe.';

    public function handle()
    {
        $this->setUnlimitedTimeLimit();
        $this->createCharacterDirectoryIfNotExists();

        $allAnime = NotifyCharacter::query()->select('id', 'notifyID', 'uniqueID', 'image_extension')->get()->toArray();

        $this->processAllAnime($allAnime);
    }

    private function setUnlimitedTimeLimit()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);
    }

    private function createCharacterDirectoryIfNotExists()
    {
        if (!Storage::disk('local')->exists('characters')) {
            Storage::disk('local')->makeDirectory('characters');
        }
    }

    private function processAllAnime($allAnime)
    {
        foreach ($allAnime as $item) {
            if (!empty($item['image_extension'])) {
                $this->processAnimeItem($item);
            }
        }
    }

    private function processAnimeItem($item)
    {
        if (Storage::disk('local')->exists('characters/' . $item['uniqueID'] . '.jpg')) {
            return;
        }

        try {
            dispatch(new NotifyCharacterImageJob($item['id'], $item['notifyID'], $item['uniqueID'], $item['image_extension']));
        } catch (Exception $ex) {
            // If there is an exception, continue with the next item
        }
    }
}