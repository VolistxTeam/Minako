<?php

namespace App\Console\Commands\MAL;

use App\Models\MALAnime;
use App\Models\NotifyAnime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Jikan\MyAnimeList\MalClient;

class EpisodeCommand extends Command
{
    private $jikan;

    protected $signature = 'minako:mal:episodes {--skip=0}';

    protected $description = 'Retrieve all episode information from MAL using internal APIs.';

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $this->info('[!] Getting all anime information...');

        $allAnime = NotifyAnime::all()->toArray();

        $totalCount = count($allAnime);
        $remainingCount = 0;
        $countdownCount = 0;

        $skipCount = 0;

        if (!empty($this->option('skip'))) {
            $skipCount = (int) $this->option('skip');
        }

        $this->jikan = new MalClient();

        foreach ($allAnime as $item) {
            $countdownCount++;

            if ($remainingCount < $skipCount) {
                $remainingCount++;
                $this->info('[-] Skipping Item ['.$remainingCount.'/'.$totalCount.']');
                continue;
            }

            if ($countdownCount > 25) {
                $countdownCount = 0;
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
                $this->error('[-] Skipping item. Reason: The item has been updated within the last 7 days. ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;
                continue;
            }

            if (($item['type'] == 'movie' || $item['type'] == 'music') && $item['episodeCount'] >= 2 && !is_array($item['mappings'])) {
                $this->error('[-] Skipping item. Reason: Not supported type. ['.$remainingCount.'/'.$totalCount.']');
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

                if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
                    $this->error('[-] Skipping item. Reason: No MAL ID found. ['.$remainingCount.'/'.$totalCount.']');
                    $remainingCount++;
                    continue;
                }

                $pageNumber = 1;
                $currentLoop = 1;
                $errorDetected = false;
                $errorMessage = '';

                while ($currentLoop <= $pageNumber) {
                    $s_continue = false;

                    while (!$s_continue) {
                        try {
                            $episodesResponse = $this->jikan->getAnimeEpisodes(new \Jikan\Request\Anime\AnimeEpisodesRequest($malID, $currentLoop));

                            foreach ($episodesResponse->getResults() as $episodeItem) {
                                $malItem = MALAnime::query()->updateOrCreate([
                                    'uniqueID'   => $item['uniqueID'],
                                    'notifyID'   => $item['notifyID'],
                                    'episode_id' => $episodeItem->getMalId(),
                                ], [
                                    'title'          => !empty($episodeItem->getTitle()) ? $episodeItem->getTitle() : null,
                                    'title_japanese' => !empty($episodeItem->getTitleJapanese()) ? $episodeItem->getTitleJapanese() : null,
                                    'title_romanji'  => !empty($episodeItem->getTitleRomanji()) ? $episodeItem->getTitleRomanji() : null,
                                    'aired'          => !empty($episodeItem->getAired()) ? $episodeItem->getAired() : null,
                                    'filler'         => (int) $episodeItem->isFiller(),
                                    'recap'          => (int) $episodeItem->isRecap(),
                                ]);

                                $malItem->touch();
                            }
                        } catch (\Exception $e) {
                            $errorDetected = true;
                        }

                        $currentLoop++;

                        $s_continue = true;
                    }

                    if ($errorDetected) {
                        break;
                    }
                }

                if ($errorDetected) {
                    $this->error('[-] '.$errorMessage.' ['.$remainingCount.'/'.$totalCount.']');
                } else {
                    $this->info('[-] Item Processed ['.$remainingCount.'/'.$totalCount.']');
                }
            } else {
                $errorMessage = 'Skipping item. Reason: No MAL binding found.';
                $this->error('[-] '.$errorMessage.' ['.$remainingCount.'/'.$totalCount.']');
            }

            $remainingCount++;
        }
    }
}
