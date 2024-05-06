<?php

namespace App\Console\Commands\MAL;

use App\Facades\JikanAPI;
use App\Models\MALAnime;
use App\Models\NotifyAnime;
use App\Repositories\AnimeRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EpisodeCommand extends Command
{
    private AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        parent::__construct();
        $this->animeRepository = $animeRepository;
    }

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

        $totalCount = count($allAnime);
        $skipCount = (int)($this->option('skip') ?? 0);

        foreach ($allAnime as $index => $anime) {
            if ($index < $skipCount) {
                $this->info('[-] Skipping Item [' . $index + 1 . '/' . $totalCount . ']');
                continue;
            }

            if (empty($anime) || Carbon::now()->subDays(7)->lessThanOrEqualTo(Carbon::createFromTimeString($anime['updated_at']))) {
                $this->error('[-] Skipping anime. Reason: The anime has been updated within the last 7 days. [' . $index + 1 . '/' . $totalCount . ']');
                continue;
            }

            if (($anime['type'] == 'movie' || $anime['type'] == 'music') && $anime['episodeCount'] >= 2 && !is_array($anime['mappings'])) {
                $this->error('[-] Skipping anime. Reason: Not supported type. [' . $index + 1 . '/' . $totalCount . ']');
                continue;
            }

            $malID = array_reduce($anime['mappings'], function ($carry, $mapping) {
                return $carry ?: ($mapping['service'] === 'myanimelist/anime' ? $mapping['serviceId'] : null);
            });

            if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
                $this->error('[-] Skipping anime. Reason: No MAL ID found. [' . $index + 1 . '/' . $totalCount . ']');
                continue;
            }

            $episodes = JikanAPI::getAnimeEpisodes($malID);

            if ($episodes != null) {
                foreach ($episodes as $episode) {
                    $this->animeRepository->createOrUpdateMALEpisode($anime, $episode);
                }
                $this->info('[-] Item Processed [' . $index + 1 . '/' . $totalCount . ']');
            } else {
                $this->error('[-] Item Not Processed [' . $index + 1 . '/' . $totalCount . ']');
            }
        }
    }
}
