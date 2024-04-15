<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Episode;
use App\Models\MALAnime;
use Illuminate\Http\JsonResponse;

class EpisodeController extends Controller
{
    public function Search($name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = MALAnime::searchByName($name, 50);
        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = Episode::fromModel($query->obj)->GetDTO();
        }

        return response()->json($response);
    }

    public function GetEpisode(int $id): JsonResponse
    {
        $episodeQuery = MALAnime::query()->where('id', $id)->first();

        if (empty($episodeQuery)) {
            return response()->json(['error' => "Episode not found: {$id}"], 404);
        }

        $response = Episode::fromModel($episodeQuery)->GetDTO();

        return response()->json($response);
    }
}
