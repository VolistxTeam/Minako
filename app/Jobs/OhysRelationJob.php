<?php

namespace App\Jobs;

use App\Classes\AnimeSearch;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;

class OhysRelationJob extends Job
{
    protected OhysTorrent $torrent;

    public function __construct(OhysTorrent $torrent)
    {
        $this->torrent = $torrent;
    }

    public function handle()
    {
        $animeSearchEngine = new AnimeSearch();
        $searchArray = $animeSearchEngine->searchByTitle($this->torrent->title, 1);

        if (!empty($searchArray)) {
            $animeUniqueID = $searchArray[0]->obj['uniqueID'];
            $this->updateOrCreateRelation($this->torrent->uniqueID, $animeUniqueID);
        }
    }

    private function updateOrCreateRelation(string $uniqueID, string $matchingID): void
    {
        OhysRelation::query()->updateOrCreate([
            'uniqueID' => $uniqueID,
        ], [
            'matchingID' => $matchingID,
        ]);
    }
}
