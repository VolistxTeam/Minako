<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\EpisodeDTO;
use App\DataTransferObjects\EpisodeInformationDTO;
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
            return EpisodeInformationDTO::fromModel($item)->GetDTO();
        });

        return response()->json($buildResponse);
    }

    public function GetEpisode(int $id): JsonResponse
    {
        $episodeQuery = MALAnime::query()->where('id', $id)->first();

        if (empty($episodeQuery)) {
            return response()->json(['error' => "Episode not found: {$id}"], 404);
        }

        $response = EpisodeDTO::fromModel($episodeQuery)->GetDTO();

        return response()->json($response);
    }
}
