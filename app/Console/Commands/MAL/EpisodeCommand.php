<?php

namespace App\Console\Commands\MAL;

use App\Models\MALAnime;
use App\Models\NotifyAnime;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class EpisodeCommand extends Command
{
    protected $signature = "minako:mal:episodes";

    protected $description = "Retrieve all episode information from MAL using internal APIs.";

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $allAnime = NotifyAnime::all()->toArray();

        $totalCount = count($allAnime);
        $remainingCount = 1;
        $cooldownCount = 0;

        $internalAPIBaseURL = 'https://api.jikan.moe/v3/';

        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        foreach ($allAnime as $item) {
            $cooldownCount++;

            if ($cooldownCount > 15) {
                $cooldownCount = 0;
                $this->info('[+] Waiting for 10 seconds...');
                sleep(10);
            }

            $allowCrawl = false;

            if (!empty($dbItem)) {
                if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($item['created_at']))) {
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

            if ($item['type'] == 'movie' && $item['episodeCount'] >= 2) {
                $this->error('[-] Skipping item. Reason: Not supported type. [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
                continue;
            }

            if ($item['type'] == 'music' && $item['episodeCount'] >= 2) {
                $this->error('[-] Skipping item. Reason: Not supported type. [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
                continue;
            }

            if (!is_array($item['mappings'])) {
                $this->error('[-] Skipping item. Reason: Not supported type. [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
                continue;
            }

            if (array_search('myanimelist/anime', array_column($item['mappings'], 'service'))) {
                $malID = '';

                foreach ($item['mappings'] as $value) {
                    if ($value['service'] == 'myanimelist/anime') {
                        $malID = $value['serviceId'];
                    }
                }

                if (empty($malID)) {
                    $this->error('[-] Skipping item. Reason: No MAL ID found. [' . $remainingCount . '/' . $totalCount . ']');
                    $remainingCount++;
                    continue;
                }

                $headers = [
                    'User-Agent' => 'minako-v2-access-05819xm26',
                ];

                $pageNumber = 1;
                $currentLoop = 1;
                $errorDetected = false;
                $errorMessage = '';

                while ($currentLoop <= $pageNumber) {
                    $s_continue = false;

                    while (!$s_continue) {
                        $episodesResponse = $client->get($internalAPIBaseURL . 'anime/' . $malID . '/episodes/' . $currentLoop, ['headers' => $headers]);

                        if ($episodesResponse->getStatusCode() == 429) {
                            $this->error('[-] Rate limited. Waiting 5 seconds to retry...');
                            sleep(5);
                            $s_continue = false;
                            continue;
                        }

                        if ($episodesResponse->getStatusCode() != 200) {
                            $errorMessage = 'Skipping item. Reason: Something went wrong.';
                            $s_continue = true;
                            $errorDetected = true;
                            $currentLoop = $pageNumber + 1;
                            continue;
                        }

                        $episodes = (string)$episodesResponse->getBody();

                        $episodes = json_decode($episodes, true);

                        if (empty($episodes['episodes'])) {
                            $errorMessage = 'Skipping item. Reason: Episode not found.';
                            $s_continue = true;
                            $errorDetected = true;
                            $currentLoop = $pageNumber + 1;
                            continue;
                        }

                        if ($episodes['episodes_last_page'] > 1) {
                            $pageNumber = $episodes['episodes_last_page'];
                        }

                        foreach ($episodes['episodes'] as $episodeItem) {
                            $malItem = MALAnime::query()->updateOrCreate([
                                'uniqueID' => $item['uniqueID'],
                                'notifyID' => $item['notifyID'],
                                'episode_id' => $episodeItem['episode_id']
                            ], [
                                'title' => !empty($episodeItem['title']) ? $episodeItem['title'] : null,
                                'title_japanese' => !empty($episodeItem['title_japanese']) ? $episodeItem['title_japanese'] : null,
                                'title_romanji' => !empty($episodeItem['title_romanji']) ? $episodeItem['title_romanji'] : null,
                                'aired' => !empty($episodeItem['aired']) ? $episodeItem['aired'] : null,
                                'filler' => !empty($episodeItem['filler']) ? $episodeItem['filler'] : null,
                                'recap' => !empty($episodeItem['recap']) ? $episodeItem['recap'] : null,
                            ]);

                            $malItem->touch();
                        }

                        $currentLoop++;

                        $s_continue = true;
                    }

                    if ($errorDetected) {
                        break;
                    }
                }

                if ($errorDetected) {
                    $this->error('[-] ' . $errorMessage . ' [' . $remainingCount . '/' . $totalCount . ']');
                } else {
                    $this->info('[-] Item Processed [' . $remainingCount . '/' . $totalCount . ']');
                }

                $remainingCount++;
            } else {
                $errorMessage = 'Skipping item. Reason: No MAL binding found.';
                $this->error('[-] ' . $errorMessage . ' [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
            }
        }
    }
}
