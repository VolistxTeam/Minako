<?php

namespace App\Http\Controllers\Services;

use App\Models\MALAnime;
use Illuminate\Http\JsonResponse;

class EpisodeController extends Controller
{
    public function Search(string $name): JsonResponse
    {
        $name = urldecode($name);

        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = MALAnime::query()
            ->where('title', 'LIKE', "%$name%")
            ->orWhere('title_japanese', 'LIKE', "%$name%")
            ->orWhere('title_romanji', 'LIKE', "%$name%")
            ->take(100)
            ->paginate(50, ['*'], 'page', 1);

        $buildResponse = $searchQuery->getCollection()->map(function ($item) {
            return [
                'id'       => $item['id'],
                'anime_id' => $item['uniqueID'],
                'title'    => $item['title'],
            ];
        });

        return response()->json($buildResponse);
    }

    public function GetEpisode(int $id): JsonResponse
    {
        $episodeQuery = MALAnime::query()->where('id', $id)->first();

        if (empty($episodeQuery)) {
            return response()->json(['error' => "Episode not found: {$id}"], 404);
        }

        $buildResponse = [
            'id'             => $episodeQuery['uniqueID'],
            'episode_number' => $episodeQuery['episode_id'],
            'titles'         => [
                'english'  => !empty($episodeQuery['title']) ? trim($episodeQuery['title'], chr(0xC2).chr(0xA0)) : null,
                'japanese' => !empty($episodeQuery['title_japanese']) ? trim($episodeQuery['title_japanese'], chr(0xC2).chr(0xA0)) : null,
                'romaji'   => !empty($episodeQuery['title_romanji']) ? trim($episodeQuery['title_romanji'], chr(0xC2).chr(0xA0)) : null,
            ],
            'aired'      => $episodeQuery['aired'],
            'created_at' => (string) $episodeQuery['created_at'],
            'updated_at' => (string) $episodeQuery['updated_at'],
        ];

        return response()->json($buildResponse);
    }
}
