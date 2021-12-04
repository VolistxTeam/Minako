<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use App\Models\NotifyRelation;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class RelationCommand extends Command
{
    protected $signature = "minako:notify:relation";

    protected $description = "Retrieve all relation information for anime from notify.moe.";

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $apiBaseURL = "https://notify.moe/api/animerelations/";

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

        $allAnime = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID')->get()->toArray();

        $this->info('[!] Starting the process to crawl information...');

        $totalCount = count($allAnime);
        $remainingCount = 0;

        foreach ($allAnime as $item) {
            try {
                $dbItem = NotifyRelation::query()->where('notifyID', $item['notifyID'])->select('id', 'notifyID', 'uniqueID', 'created_at', 'updated_at')->first();
                $allowCrawl = false;

                if (!empty($dbItem)) {
                    if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbItem->updated_at))) {
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

                $relationResponse = $client->get($realAPIURL, ['headers' => $headers]);

                if ($relationResponse->getStatusCode() != 200) {
                    $this->error('[-] Skipping item. Reason: The item does not found. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $downloadedData = (string)$relationResponse->getBody();

                $downloadedData = json_decode($downloadedData, true);
                $uniqueIDGen = substr(sha1('jUWxEBxWY8' . $item['notifyID'] . 'CHuDkBNUhqcm19MVTjtf'), 0, 8);

                if (!empty($downloadedData['items'])) {
                    foreach ($downloadedData['items'] as &$value) {
                        $value['uniqueID'] = substr(sha1('jUWxEBxWY8' . $value['animeId'] . 'CHuDkBNUhqcm19MVTjtf'), 0, 8);
                        $value['notifyID'] = $value['animeId'];
                        unset($value['animeId']);
                    }
                }

                $notifyDBItem = NotifyRelation::query()->updateOrCreate([
                    'uniqueID' => $uniqueIDGen,
                    'notifyID' => $item['notifyID'],
                ], [
                    'items' => !empty($downloadedData['items']) ? $downloadedData['items'] : null
                ]);

                $notifyDBItem->touch();

                $this->info('[+] Item Saved [' . $remainingCount . '/' . $totalCount . ']');

            } catch (Exception $ex) {
                $this->error('[-] Skipping item. Reason: Unknown Error [' . $remainingCount . '/' . $totalCount . ']');
            }

            $remainingCount++;
        }
    }
}
