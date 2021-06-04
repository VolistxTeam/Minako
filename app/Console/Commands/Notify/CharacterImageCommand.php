<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyCharacter;
use Exception;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class CharacterImageCommand extends Command
{
    protected $signature = "minako:notify:character-image";

    protected $description = "Download character images from notify.moe.";

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $exists = Storage::disk('local')->exists('minako/characters');

        if (!$exists) {
            Storage::disk('local')->makeDirectory('minako/characters');
        }

        $allAnime = NotifyCharacter::query()->select('id', 'notifyID', 'uniqueID', 'image_extension')->get()->toArray();

        $totalCount = count($allAnime);
        $remainingCount = 1;

        $faker = Factory::create();

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => $faker->chrome,
        ];

        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        foreach ($allAnime as $item) {
            if (!empty($item['image_extension'])) {
                $existsFile = Storage::disk('local')->exists('minako/characters/' . $item['uniqueID'] . '.jpg');

                if ($existsFile) {
                    $this->error('[+] Character exists. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $originalImage = 'https://media.notify.moe/images/characters/original/' . $item['notifyID'] . $item['image_extension'];
                $largeImage = 'https://media.notify.moe/images/characters/large/' . $item['notifyID'] . $item['image_extension'];

                $fp = tmpfile();
                $fpPath = stream_get_meta_data($fp)["uri"];

                $imageResponse = $client->request('GET', $largeImage, ['headers' => $headers, 'sink' => $fpPath]);

                if ($imageResponse->getStatusCode() != 200) {
                    $imageResponse = $client->request('GET', $originalImage, ['headers' => $headers, 'sink' => $fpPath]);

                    if ($imageResponse->getStatusCode() != 200) {
                        $this->error('[+] Cannot find character image. Ignoring... [' . $remainingCount . '/' . $totalCount . ']');
                        $remainingCount++;
                        fclose($fp);
                        continue;
                    }
                }

                try {
                    $manager = new ImageManager(array('driver' => 'gd'));

                    $image = $manager->make($fpPath)->stream('jpg', 100);

                    Storage::disk('local')->put('minako/characters/' . $item['uniqueID'] . '.jpg', $image);

                    unset($fp);

                    $this->info('[+] Character image uploaded for ID ' . $item['notifyID'] . ' [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                } catch (Exception $ex) {
                    $this->error('[+] Exception [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }
            } else {
                $this->error('[+] Character not found. [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
            }
        }
    }
}
