<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Anime;
use App\DataTransferObjects\Character;
use App\DataTransferObjects\Company;
use App\DataTransferObjects\Episode;
use App\DataTransferObjects\Mapping;
use App\DataTransferObjects\Relation;
use App\DataTransferObjects\Torrent;
use App\Helpers\JikanAPI;
use App\Helpers\OhysBlacklistChecker;
use App\Models\MALAnime;
use App\Models\NotifyAnime;
use App\Models\NotifyAnimeCharacter;
use App\Models\NotifyCharacter;
use App\Models\NotifyCompany;
use App\Repositories\AnimeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class AnimeController extends Controller
{
    private AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        $this->animeRepository = $animeRepository;
    }

    public function Search(Request $request, $name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $type = $request->input('type');

        $searchQuery = $this->animeRepository->searchNotifyAnimeByTitle($name, 50, $type ?? null);

        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = Anime::fromModel($query->obj)->GetDTO();
        }

        return response()->json($response);
    }

    public function GetItem($uniqueID)
    {
        $itemQuery = NotifyAnime::query()
            ->where('uniqueID', $uniqueID)
            ->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $response = Anime::fromModel($itemQuery)->GetDTO();

        return response()->json($response);
    }

    public function GetImage($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $id = $itemQuery->uniqueID;

        $contents = Storage::disk('local')->get('posters/' . $id . '.jpg');

        if (empty($contents)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        return Response::make($contents, 200)->header('Content-Type', 'image/jpeg');
    }

    public function GetEpisode($uniqueID, $episodeNumber)
    {
        $itemQuery = NotifyAnime::query()->latest()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $episodeQuery = $itemQuery->episodes->where('episode_id', $episodeNumber)->first();

        if (empty($episodeQuery)) {
            return response('Episode not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $response = Episode::fromModel($episodeQuery)->GetDTO();

        return response()->json($response);
    }

    public function GetTorrents($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->latest()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $itemsFiltered = $itemQuery->torrents->filter(function ($torrent) use ($itemQuery) {
            return !OhysBlacklistChecker::isBlacklistedTitle($itemQuery->title_canonical ?? '') || !OhysBlacklistChecker::isBlacklistedTitle($itemQuery->title_romaji ?? '');
        })->map(function ($torrent) {
            return Torrent::fromModel($torrent)->GetDTO();
        });

        return response()->json($itemsFiltered);
    }

    public function GetEpisodes(Request $request, $uniqueID)
    {
        $paginateNumber = $request->input('p', 1);

        if (filter_var($paginateNumber, FILTER_VALIDATE_INT) === false) {
            $paginateNumber = 1;
        }

        $searchQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($searchQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        if (empty($searchQuery->episodes)) {
            return response('Episodes not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $episodesQuery = self::customPaginate($searchQuery->episodes, 15, $paginateNumber);

        $items = [];
        foreach ($episodesQuery as $episodeQuery) {
            $items[] = Episode::fromModel($episodeQuery)->GetDTO();
        }

        $buildResponse = [
            'pagination' => [
                'per_page' => $episodesQuery->perPage(),
                'current' => $episodesQuery->currentPage(),
                'total' => $episodesQuery->lastPage(),
            ],
            'items' => $items,
        ];

        return response()->json($this->utf8ize($buildResponse));
    }

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            array_walk_recursive($mixed, function (&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            });
        } elseif (is_string($mixed)) {
            $mixed = mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }

        return $mixed;
    }

    public function SyncEpisodes(Request $request, $uniqueID)
    {
        $jikan = new JikanAPI();

        $item = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (($item['type'] == 'movie' || $item['type'] == 'music') && $item['episodeCount'] >= 2 && !is_array($item['mappings'])) {
            return response('Not supported type: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $malID = collect($item['mappings'])->firstWhere('service', 'myanimelist/anime')['serviceId'];

        if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
            return response('No MAL ID found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $data = $jikan->getAnimeEpisodes($malID);

        if ($data != null) {
            foreach ($data as $episodeItem) {
                $malItem = MALAnime::query()->updateOrCreate([
                    'uniqueID' => $item['uniqueID'],
                    'notifyID' => $item['notifyID'],
                    'episode_id' => $episodeItem['mal_id'],
                ], [
                    'title' => !empty($episodeItem['title']) ? $episodeItem['title'] : null,
                    'title_japanese' => !empty($episodeItem['title_japanese']) ? $episodeItem['title_japanese'] : null,
                    'title_romanji' => !empty($episodeItem['title_romanji']) ? $episodeItem['title_romanji'] : null,
                    'aired' => !empty($episodeItem['aired']) ? Carbon::parse($episodeItem['aired']) : null,
                    'filler' => (int)$episodeItem['filler'],
                    'recap' => (int)$episodeItem['recap'],
                ]);

                $malItem->touch();
            }
        }

        return response('Sync successfully.', 200)->header('Content-Type', 'text/plain');
    }

    public function GetMappings($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [
            [
                'service' => 'notify/anime',
                'service_id' => (string)$itemQuery->notifyID,
            ],
        ];

        foreach ($itemQuery->mappings ?? [] as $item) {
            $filteredMappingData[] = Mapping::fromModel($item)->GetDTO();
        }

        $response = [
            'id' => $itemQuery['uniqueID'],
            'mappings' => $filteredMappingData,
        ];

        return response()->json($response);
    }

    public function GetStudios($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredStudioData = [];

        foreach ($itemQuery->studios as $item) {
            $studioQuery = NotifyCompany::query()->where('notifyID', $item)->first();
            if (!empty($studioQuery)) {
                $filteredStudioData[] = Company::fromModel($studioQuery)->GetDTO();
            }
        }

        $response = [
            'id' => $itemQuery['uniqueID'],
            'studios' => $filteredStudioData,
        ];

        return response()->json($response);
    }

    public function GetProducers($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredProducerData = [];

        foreach ($itemQuery->producers ?? [] as $item) {
            $producerQuery = NotifyCompany::query()->latest()->where('notifyID', $item)->first();
            if (!empty($producerQuery)) {
                $filteredProducerData[] = Company::fromModel($producerQuery)->GetDTO();
            }
        }

        $response = [
            'id' => $itemQuery['uniqueID'],
            'producers' => $filteredProducerData,
        ];

        return response()->json($response);
    }

    public function GetLicensors($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredLicensorData = [];

        foreach ($itemQuery->licensors ?? [] as $item) {
            $licensorQuery = NotifyCompany::query()->where('notifyID', $item)->first();

            if (!empty($licensorQuery)) {
                $filteredLicensorData[] = Company::fromModel($licensorQuery)->GetDTO();
            }
        }

        $response = [
            'id' => $itemQuery['uniqueID'],
            'licensors' => $filteredLicensorData,
        ];

        return response()->json($response);
    }

    public function GetRelations($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredRelationData = [];

        $itemArray = $itemQuery->relations;

        if (!empty($itemArray) && !empty($itemArray['items'])) {
            foreach ($itemArray['items'] as $item) {
                $filteredRelationData[] = Relation::fromModel($item)->GetDTO();
            }
        }

        $response = [
            'id' => $itemQuery['uniqueID'],
            'relations' => $filteredRelationData,
        ];

        return response()->json($response);
    }

    public function GetCharacters($uniqueID)
    {
        $animeCharacter = NotifyAnimeCharacter::query()->where('uniqueID', $uniqueID)->first();

        if (empty($animeCharacter)) {
            return response('Character not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredCharacterData = [];

        foreach ($animeCharacter->items ?? [] as $item) {
            $characterQuery = NotifyCharacter::query()->where('notifyID', $item['characterId'])->first();

            if (!empty($characterQuery)) {
                $filteredCharacterData[] = Character::fromModel($characterQuery)->GetDTO();
            }
        }

        return response()->json($filteredCharacterData);
    }
}
