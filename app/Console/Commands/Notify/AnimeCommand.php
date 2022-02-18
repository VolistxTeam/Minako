<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use Carbon\Carbon;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class AnimeCommand extends Command
{
    protected $signature = 'minako:notify:anime';

    protected $description = 'Retrieve all anime information from notify.moe.';

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $sitemapURL = 'https://notify.moe/sitemap/anime.txt';
        $apiBaseURL = 'https://notify.moe/api/anime/';
        $notifyBaseURL = 'https://notify.moe/anime/';

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
            $dbItem = NotifyAnime::query()->where('notifyID', $item)->first();
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

            $apiResponse = $client->get($realAPIURL, ['headers' => $headers]);

            if ($apiResponse->getStatusCode() != 200) {
                $this->error('[-] Skipping item. Reason: The item does not found. ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;
                continue;
            }

            $downloadedData = (string) $apiResponse->getBody();

            if (!$this->is_json($downloadedData)) {
                $this->error('[-] Skipping item. Reason: The item is in an unsupported format. ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;
                continue;
            }

            $downloadedData = json_decode($downloadedData, true);
            $uniqueIDGen = substr(sha1('jUWxEBxWY8'.$item.'CHuDkBNUhqcm19MVTjtf'), 0, 8);

            $notifyDBItem = NotifyAnime::query()->updateOrCreate([
                'uniqueID' => $uniqueIDGen,
                'notifyID' => $downloadedData['id'],
            ], [
                'type'              => !empty($downloadedData['type']) ? $downloadedData['type'] : null,
                'title_canonical'   => !empty($downloadedData['title']['canonical']) ? $downloadedData['title']['canonical'] : null,
                'title_romaji'      => !empty($downloadedData['title']['romaji']) ? $downloadedData['title']['romaji'] : null,
                'title_english'     => !empty($downloadedData['title']['english']) ? $downloadedData['title']['english'] : null,
                'title_japanese'    => !empty($downloadedData['title']['japanese']) ? $downloadedData['title']['japanese'] : null,
                'title_hiragana'    => !empty($downloadedData['title']['hiragana']) ? $downloadedData['title']['hiragana'] : null,
                'title_synonyms'    => !empty($downloadedData['title']['synonyms']) ? $downloadedData['title']['synonyms'] : null,
                'summary'           => !empty($downloadedData['summary']) ? $downloadedData['summary'] : null,
                'status'            => !empty($downloadedData['status']) ? $downloadedData['status'] : null,
                'genres'            => !empty($downloadedData['genres']) ? $downloadedData['genres'] : null,
                'startDate'         => !empty($downloadedData['startDate']) ? $downloadedData['startDate'] : null,
                'endDate'           => !empty($downloadedData['endDate']) ? $downloadedData['endDate'] : null,
                'episodeCount'      => !empty($downloadedData['episodeCount']) ? $downloadedData['episodeCount'] : null,
                'episodeLength'     => !empty($downloadedData['episodeLength']) ? $downloadedData['episodeLength'] : null,
                'source'            => !empty($downloadedData['source']) ? $downloadedData['source'] : null,
                'image_extension'   => !empty($downloadedData['image']['extension']) ? $downloadedData['image']['extension'] : null,
                'image_width'       => !empty($downloadedData['image']['width']) ? $downloadedData['image']['width'] : null,
                'image_height'      => !empty($downloadedData['image']['height']) ? $downloadedData['image']['height'] : null,
                'firstChannel'      => !empty($downloadedData['firstChannel']) ? $downloadedData['firstChannel'] : null,
                'rating_overall'    => !empty($downloadedData['rating']['overall']) ? $downloadedData['rating']['overall'] : null,
                'rating_story'      => !empty($downloadedData['rating']['story']) ? $downloadedData['rating']['story'] : null,
                'rating_visuals'    => !empty($downloadedData['rating']['visuals']) ? $downloadedData['rating']['visuals'] : null,
                'rating_soundtrack' => !empty($downloadedData['rating']['soundtrack']) ? $downloadedData['rating']['soundtrack'] : null,
                'trailers'          => !empty($downloadedData['trailers']) ? $downloadedData['trailers'] : null,
                'n_episodes'        => !empty($downloadedData['episodes']) ? $downloadedData['episodes'] : null,
                'mappings'          => !empty($downloadedData['mappings']) ? $downloadedData['mappings'] : null,
                'studios'           => !empty($downloadedData['studios']) ? $downloadedData['studios'] : null,
                'producers'         => !empty($downloadedData['producers']) ? $downloadedData['producers'] : null,
                'licensors'         => !empty($downloadedData['licensors']) ? $downloadedData['licensors'] : null,
            ]);

            $notifyDBItem->touch();

            $this->line('[+] Item Saved ['.$remainingCount.'/'.$totalCount.']');
            $remainingCount++;
        }
    }

    private function is_json($string): bool
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
