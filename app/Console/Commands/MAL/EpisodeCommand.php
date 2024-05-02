<?php

namespace App\Console\Commands\MAL;

use App\Facades\JikanAPI;
use App\Models\MALAnime;
use App\Models\NotifyAnime;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EpisodeCommand extends Command
{
    protected $signature = 'minako:mal:episodes {--skip=0}';

    protected $description = 'Retrieve all episode information from MAL using Jikan APIs.';

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

        $allAnime = NotifyAnime::query()->where('type', '!=', 'movie')->cursor();
        $totalCount = count($allAnime);
        $remainingCount = 0;

        $skipCount = 0;

        if (! empty($this->option('skip'))) {
            $skipCount = (int) $this->option('skip');
        }

        foreach ($allAnime as $item) {

            if ($remainingCount < $skipCount) {
                $remainingCount++;
                $this->info('[-] Skipping Item ['.$remainingCount.'/'.$totalCount.']');

                continue;
            }

            $allowCrawl = false;

            if (! empty($dbItem)) {
                if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($item['updated_at']))) {
                    $allowCrawl = true;
                }
            } else {
                $allowCrawl = true;
            }

            if (! $allowCrawl) {
                $this->error('[-] Skipping item. Reason: The item has been updated within the last 7 days. ['.$remainingCount.'/'.$totalCount.']');
                $remainingCount++;

                continue;
            }

            if (($item['type'] == 'movie' || $item['type'] == 'music') && $item['episodeCount'] >= 2 && ! is_array($item['mappings'])) {
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

            $data = JikanAPI::getAnimeEpisodes($malID);

            if ($data != null) {
                foreach ($data as $episodeItem) {
                    $malItem = MALAnime::query()->updateOrCreate([
                        'uniqueID' => $item['uniqueID'],
                        'notifyID' => $item['notifyID'],
                        'episode_id' => $episodeItem['mal_id'],
                    ], [
                        'title' => ! empty($episodeItem['title']) ? $episodeItem['title'] : null,
                        'title_japanese' => ! empty($episodeItem['title_japanese']) ? $episodeItem['title_japanese'] : null,
                        'title_romanji' => ! empty($episodeItem['title_romanji']) ? $episodeItem['title_romanji'] : null,
                        'aired' => ! empty($episodeItem['aired']) ? \Illuminate\Support\Carbon::parse($episodeItem['aired']) : null,
                        'filler' => (int) $episodeItem['filler'],
                        'recap' => (int) $episodeItem['recap'],
                    ]);

                    $malItem->touch();
                }

                $this->info('[-] Item Processed ['.$remainingCount.'/'.$totalCount.']');
            } else {
                $this->error('[-] Item Not Processed ['.$remainingCount.'/'.$totalCount.']');
            }

            $remainingCount++;
        }
    }
}
