<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Anime;
use App\DataTransferObjects\Character;
use App\DataTransferObjects\Company;
use App\DataTransferObjects\Episode;
use App\DataTransferObjects\Mapping;
use App\DataTransferObjects\Relation;
use App\DataTransferObjects\Torrent;
use App\Facades\JikanAPI;
use App\Facades\OhysBlacklist;
use App\Repositories\AnimeRepository;
use Illuminate\Http\Request;
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
        $item = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($item)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $response = Anime::fromModel($item)->GetDTO();

        return response()->json($response);
    }

    public function GetImage($uniqueID)
    {
        $id = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID)?->uniqueID;

        if (empty($id)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $contents = Storage::disk('local')->get('posters/' . $id . '.jpg');

        if (empty($contents)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        return Response::make($contents)->header('Content-Type', 'image/jpeg');
    }

    public function GetEpisode($uniqueID, $episodeNumber)
    {
        $item = $this->animeRepository->getNotifyAnimeEpisode($uniqueID, $episodeNumber);

        if (empty($item)) {
            return response('Episode not found', 404)->header('Content-Type', 'text/plain');
        }

        $response = Episode::fromModel($item)->GetDTO();

        return response()->json($response);
    }

    public function GetTorrents($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID, true);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $itemsFiltered = $anime->torrents->filter(function ($torrent) use ($anime) {
            return !OhysBlacklist::isBlacklistedTitle($anime->title_canonical ?? '') || !OhysBlacklist::isBlacklistedTitle($anime->title_romaji ?? '');
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

        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        if (empty($anime->episodes)) {
            return response('Episodes not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $episodes = self::customPaginate($anime->episodes, 15, $paginateNumber);

        $items = [];
        foreach ($episodes as $episode) {
            $items[] = Episode::fromModel($episode)->GetDTO();
        }

        $response = [
            'pagination' => [
                'per_page' => $episodes->perPage(),
                'current' => $episodes->currentPage(),
                'total' => $episodes->lastPage(),
            ],
            'items' => $items,
        ];

        return response()->json($this->utf8ize($response));
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

        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (($anime['type'] == 'movie' || $anime['type'] == 'music') && $anime['episodeCount'] >= 2 && !is_array($anime['mappings'])) {
            return response('Not supported type: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $malID = collect($anime['mappings'])->firstWhere('service', 'myanimelist/anime')['serviceId'];

        if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
            return response('No MAL ID found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $data = JikanAPI::getAnimeEpisodes($malID);

        if ($data != null) {
            foreach ($data as $episode) {
                $this->animeRepository->createOrUpdateMALEpisode($anime, $episode);
            }
        }

        return response('Sync successfully.')->header('Content-Type', 'text/plain');
    }

    public function GetMappings($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [
            [
                'service' => 'notify/anime',
                'service_id' => (string)$anime->notifyID,
            ],
        ];

        foreach ($anime->mappings ?? [] as $item) {
            $filteredMappingData[] = Mapping::fromModel($item)->GetDTO();
        }

        $response = [
            'id' => $anime['uniqueID'],
            'mappings' => $filteredMappingData,
        ];

        return response()->json($response);
    }

    public function GetStudios($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredStudioData = [];

        foreach ($anime->studios as $studioId) {
            $studioQuery = $this->animeRepository->getNotifyCompanyByNotifyId($studioId);
            if (!empty($studioQuery)) {
                $filteredStudioData[] = Company::fromModel($studioQuery)->GetDTO();
            }
        }

        $response = [
            'id' => $anime['uniqueID'],
            'studios' => $filteredStudioData,
        ];

        return response()->json($response);
    }

    public function GetProducers($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredProducerData = [];

        foreach ($anime->producers ?? [] as $producerId) {
            $producer = $this->animeRepository->getNotifyCompanyByNotifyId($producerId, true);
            if (!empty($producer)) {
                $filteredProducerData[] = Company::fromModel($producer)->GetDTO();
            }
        }

        $response = [
            'id' => $anime['uniqueID'],
            'producers' => $filteredProducerData,
        ];

        return response()->json($response);
    }

    public function GetLicensors($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredLicensorData = [];

        foreach ($anime->licensors ?? [] as $licensorId) {
            $license = $this->animeRepository->getNotifyCompanyByNotifyId($licensorId);

            if (!empty($license)) {
                $filteredLicensorData[] = Company::fromModel($license)->GetDTO();
            }
        }

        $response = [
            'id' => $anime['uniqueID'],
            'licensors' => $filteredLicensorData,
        ];

        return response()->json($response);
    }

    public function GetRelations($uniqueID)
    {
        $anime = $this->animeRepository->getNotifyAnimeByUniqueID($uniqueID);

        if (empty($anime)) {
            return response('Key not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredRelationData = [];

        $relations = $anime->relations;

        if (!empty($relations) && !empty($relations['items'])) {
            foreach ($relations['items'] as $item) {
                $filteredRelationData[] = Relation::fromModel($item)->GetDTO();
            }
        }

        $response = [
            'id' => $anime['uniqueID'],
            'relations' => $filteredRelationData,
        ];

        return response()->json($response);
    }

    public function GetCharacters($uniqueID)
    {
        $animeCharacters = $this->animeRepository->getNotifyAnimeCharactersByUniqueId($uniqueID);

        if (empty($animeCharacters)) {
            return response('Character not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredCharacterData = [];

        foreach ($animeCharacters->items ?? [] as $notifyCharacter) {
            $notifyCharacter = $this->animeRepository->getNotifyCharacterByNotifyId($notifyCharacter['characterId']);

            if (!empty($notifyCharacter)) {
                $filteredCharacterData[] = Character::fromModel($notifyCharacter)->GetDTO();
            }
        }

        return response()->json($filteredCharacterData);
    }
}
