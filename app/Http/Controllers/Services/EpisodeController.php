<?php

namespace App\Http\Controllers\Services;

use App\Models\MALAnime;

class EpisodeController extends Controller
{
    public function Search($name)
    {
        $searchQuery = MALAnime::search($this->escapeElasticReservedChars($name))->paginate(50, 'page', 1);

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = array();
            $newArray['id'] = $item['id'];
            $newArray['anime_id'] = $item['uniqueID'];
            $newArray['episode_title'] = $item['title'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetEpisode($id)
    {
        $episodeQuery = MALAnime::query()->where('id', $id)->first();

        if (empty($episodeQuery)) {
            return response('Episode not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $buildResponse = [
            'id' => $episodeQuery['uniqueID'],
            'episode_number' => $episodeQuery['episode_id'],
            'titles' => [
                'english' => !empty($episodeQuery['title']) ? trim($episodeQuery['title'], chr(0xC2) . chr(0xA0)) : null,
                'japanese' => !empty($episodeQuery['title_japanese']) ? trim($episodeQuery['title_japanese'], chr(0xC2) . chr(0xA0)) : null,
                'romaji' => !empty($episodeQuery['title_romanji']) ? trim($episodeQuery['title_romanji'], chr(0xC2) . chr(0xA0)) : null,
            ],
            'aired' => $episodeQuery['aired'],
            'created_at' => (string)$episodeQuery['created_at'],
            'updated_at' => (string)$episodeQuery['updated_at']
        ];

        return response()->json($buildResponse);
    }
}
