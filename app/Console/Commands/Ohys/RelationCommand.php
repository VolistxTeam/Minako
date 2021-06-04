<?php

namespace App\Console\Commands\Ohys;

use App\Classes\AnimeSearch;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use Illuminate\Console\Command;

class RelationCommand extends Command
{
    protected $signature = "minako:ohys:relation";

    protected $description = "Search and save relationships for ohys torrents.";

    public function handle()
    {
        set_time_limit(0);

        $animeSearchEngine = new AnimeSearch();
        $allTorrents = OhysTorrent::all()->toArray();

        foreach ($allTorrents as $torrent) {
            $existCheck = OhysRelation::query()->where('uniqueID', $torrent['uniqueID'])->first();

            if (!empty($existCheck)) {
                continue;
            }

            $searchArray = $animeSearchEngine->SearchByTitle($torrent['title'], 1);

            if (empty($searchArray)) {
                continue;
            }

            $anime_uniqueID = $searchArray[0]->obj['uniqueID'];

            OhysRelation::query()->updateOrCreate([
                'uniqueID' => $torrent['uniqueID']
            ], [
                'matchingID' => $anime_uniqueID
            ]);


            $this->info('[Debug] Done: ' . $anime_uniqueID . ' (' . $searchArray[0]->obj['title_canonical'] . ') -> ' . $torrent['uniqueID'] . ' (' . $torrent['torrentName'] . ')');
        }

        return 0;
    }
}
