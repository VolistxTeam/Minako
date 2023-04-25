<?php

namespace App\Console\Commands\MAL;

use App\Models\MALAnime;
use App\Models\NotifyAnime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Jikan\Exception\BadResponseException;
use Jikan\Exception\ParserException;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeEpisodesRequest;

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

        $oneMonthAgo = Carbon::now()->subMonth();

        $allAnime = NotifyAnime::query()
            ->where('status', 'current')
            ->where('type', '!=', 'movie')
            ->orWhere(function ($query) use ($oneMonthAgo) {
                $query->where('status', 'finished')
                    ->whereRaw("(
                (LENGTH(endDate) = 10 AND STR_TO_DATE(endDate, '%Y-%m-%d') > ?) OR
                (LENGTH(endDate) = 7 AND STR_TO_DATE(CONCAT(endDate, '-01'), '%Y-%m-%d') > ?) OR
                (LENGTH(endDate) = 4 AND STR_TO_DATE(CONCAT(endDate, '-01-01'), '%Y-%m-%d') > ?)
            )", [$oneMonthAgo, $oneMonthAgo, $oneMonthAgo]);
            })
            ->cursor();

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

            if ($countdownCount > 10) {
                $countdownCount = 0;
                $this->info('[+] Waiting for 20 seconds...');
                sleep(20);
            }

            $allowCrawl = false;

            if (!empty($dbItem)) {
                if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($item['updated_at']))) {
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

            $malID = null;

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
                        $episodesResponse = $this->jikan->getAnimeEpisodes(new AnimeEpisodesRequest((int) $malID, $currentLoop));

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
                    } catch (BadResponseException|ParserException $e) {
                        $errorDetected = true;
                        $errorMessage = $e->getMessage();
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

            $remainingCount++;
        }
    }
}
