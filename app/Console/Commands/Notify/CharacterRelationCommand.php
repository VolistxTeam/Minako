<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use App\Models\NotifyCharacterRelation;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CharacterRelationCommand extends Command
{
    protected $signature = "minako:notify:character-relation";

    protected $description = "Retrieve all character relation information from notify.moe.";

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $apiBaseURL = "https://notify.moe/api/animecharacters/";

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

        $allAnime = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID', 'studios', 'producers', 'licensors')->get()->toArray();

        $this->info('[!] Starting the process to crawl information...');

        $totalCount = count($allAnime);
        $remainingCount = 1;

        foreach ($allAnime as $item) {
            try {
                $dbItem = NotifyCharacterRelation::query()->where('notifyID', $item)->select('id', 'notifyID', 'uniqueID', 'created_at', 'updated_at')->first();
                $allowCrawl = false;

                if (!empty($dbItem)) {
                    if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbItem['updated_at']))) {
                        $allowCrawl = true;
                    }
                } else {
                    $allowCrawl = true;
                }

                if (!$allowCrawl) {
                    $this->error('[-] Skipping item. Reason: The item has been updated within the last 7 days. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $realAPIURL = $apiBaseURL . $item['notifyID'];

                $characterResponse = $client->get($realAPIURL, ['headers' => $headers]);

                if ($characterResponse->getStatusCode() != 200) {
                    $this->error('[-] Skipping item. Reason: The item does not found. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $downloadedData = (string)$characterResponse->getBody();

                $downloadedData = json_decode($downloadedData, true);

                if (empty($downloadedData['items'])) {
                    $this->error('[-] Skipping item. Reason: The item does not found. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $notifyDBItem = NotifyCharacterRelation::query()->updateOrCreate([
                    'uniqueID' => $item['uniqueID'],
                    'notifyID' => $downloadedData['animeId'],
                ], [
                    'items' => !empty($downloadedData['items']) ? $downloadedData['items'] : null
                ]);

                $notifyDBItem->touch();

                $this->line('[+] Item Saved [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
            } catch (Exception $ex) {
                $this->error('[-] Skipping item. Reason: Unknown Error [' . $ex . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
                continue;
            }
        }
    }
}
