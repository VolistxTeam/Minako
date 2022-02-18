<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyCharacter;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CharacterCommand extends Command
{
    protected $signature = 'minako:notify:characters';

    protected $description = 'Retrieve all character information from notify.moe.';

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $sitemapURL = 'https://notify.moe/sitemap/character.txt';
        $apiBaseURL = 'https://notify.moe/api/character/';
        $notifyBaseURL = 'https://notify.moe/character/';

        $this->info('[!] Downloading ID lists from the server...');

        $faker = Factory::create();

        $headers = [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control'   => 'max-age=0',
            'Connection'      => 'keep-alive',
            'Keep-Alive'      => '300',
            'User-Agent'      => $faker->chrome,
        ];

        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        $sitemapResponse = $client->get($sitemapURL, ['headers' => $headers]);

        if ($sitemapResponse->getStatusCode() != 200) {
            $this->error('[-] The sitemap does not found.');

            return;
        }

        $rawNotifyIDs = explode("\n", (string) $sitemapResponse->getBody());

        $notifyIDs = [];

        $this->info('[!] Parsing IDs from links...');

        foreach ($rawNotifyIDs as $item) {
            $notifyIDs[] = str_replace($notifyBaseURL, '', $item);
        }

        unset($rawNotifyIDs);

        $this->info('[!] Starting the process to crawl information...');

        $totalCount = count($notifyIDs);
        $remainingCount = 0;

        foreach ($notifyIDs as $item) {
            try {
                $dbItem = NotifyCharacter::query()->where('notifyID', $item)->select('id', 'notifyID', 'uniqueID', 'created_at', 'updated_at')->first();
                $allowCrawl = false;

                if (!empty($dbItem)) {
                    if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbItem->updated_at))) {
                        $allowCrawl = true;
                    }
                } else {
                    $allowCrawl = true;
                }

                if (!$allowCrawl) {
                    $this->error('[-] Skipping item. Reason: The item has been updated within the last 7 days. ['.$remainingCount.'/'.$totalCount.']');
                    $remainingCount++;
                    continue;
                }

                $realAPIURL = $apiBaseURL.$item;

                $characterResponse = $client->get($realAPIURL, ['headers' => $headers]);

                if ($characterResponse->getStatusCode() != 200) {
                    $this->error('[-] Skipping item. Reason: The item does not found. ['.$remainingCount.'/'.$totalCount.']');
                    $remainingCount++;
                    continue;
                }

                $downloadedData = (string) $characterResponse->getBody();

                if (!$this->is_json($downloadedData)) {
                    $this->error('[-] Skipping item. Reason: The item is in an unsupported format. ['.$remainingCount.'/'.$totalCount.']');
                    $remainingCount++;
                    continue;
                }

                $downloadedData = json_decode($downloadedData, true);
                $uniqueIDGen = substr(sha1('8xzg8yZxEF'.$item.'1IYLCftOHKkGo57zBxpG'), 0, 8);

                $notifyDBItem = NotifyCharacter::query()->updateOrCreate([
                    'uniqueID' => $uniqueIDGen,
                    'notifyID' => $downloadedData['id'],
                ], [
                    'name_canonical'  => !empty($downloadedData['name']['canonical']) ? $downloadedData['name']['canonical'] : null,
                    'name_english'    => !empty($downloadedData['name']['english']) ? $downloadedData['name']['english'] : null,
                    'name_japanese'   => !empty($downloadedData['name']['japanese']) ? $downloadedData['name']['japanese'] : null,
                    'name_synonyms'   => !empty($downloadedData['name']['synonyms']) ? $downloadedData['name']['synonyms'] : null,
                    'image_extension' => !empty($downloadedData['image']['extension']) ? $downloadedData['image']['extension'] : null,
                    'image_width'     => !empty($downloadedData['image']['width']) ? $downloadedData['image']['width'] : null,
                    'image_height'    => !empty($downloadedData['image']['height']) ? $downloadedData['image']['height'] : null,
                    'description'     => !empty($downloadedData['description']) ? $downloadedData['description'] : null,
                    'spoilers'        => !empty($downloadedData['spoilers']) ? $downloadedData['spoilers'] : null,
                    'attributes'      => !empty($downloadedData['attributes']) ? $downloadedData['attributes'] : null,
                    'mappings'        => !empty($downloadedData['mappings']) ? $downloadedData['mappings'] : null,
                ]);

                $notifyDBItem->touch();

                $this->line('[+] Item Saved ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;
            } catch (Exception $ex) {
                $this->error('[-] Skipping item. Reason: Unknown Error ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;
                continue;
            }
        }
    }

    private function is_json($string): bool
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
