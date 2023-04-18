<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\AnimeDTO;
use App\DataTransferObjects\CharacterDTO;
use App\DataTransferObjects\EpisodeDTO;
use App\DataTransferObjects\LicensorDTO;
use App\DataTransferObjects\MappingDTO;
use App\DataTransferObjects\ProducerDTO;
use App\DataTransferObjects\RelationDTO;
use App\DataTransferObjects\StudioDTO;
use App\Models\MALAnime;
use App\Models\NotifyAnime;
use App\Models\NotifyCharacter;
use App\Models\NotifyCharacterRelation;
use App\Models\NotifyCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Jikan\Exception\BadResponseException;
use Jikan\Exception\ParserException;
use Jikan\MyAnimeList\MalClient;
use Jikan\Request\Anime\AnimeEpisodesRequest;

class AnimeController extends Controller
{
    public function Search(Request $request, $name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $type = $request->input('type');

        $searchQuery = NotifyAnime::searchByTitle($name, 50, $type ?? null);

        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = AnimeDTO::fromModel($query->obj);
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

        $response = AnimeDTO::fromModel($itemQuery);

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

        $response = EpisodeDTO::fromModel($episodeQuery)->GetDTO();

        return response()->json($response);
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
            $items[] = EpisodeDTO::fromModel($episodeQuery)->GetDTO();
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
        $jikan = new MalClient();

        $item = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (($item['type'] == 'movie' || $item['type'] == 'music') && $item['episodeCount'] >= 2 && !is_array($item['mappings'])) {
            return response('Not supported type: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $malID = collect($item['mappings'])->firstWhere('service', 'myanimelist/anime')['serviceId'];

        if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
            return response('No MAL ID found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $pageNumber = 1;
        $currentLoop = 1;
        $errorDetected = false;
        $errorMessage = '';

        while ($currentLoop <= $pageNumber) {
            $s_continue = false;

            while (!$s_continue) {
                try {
                    $episodesResponse = $jikan->getAnimeEpisodes(new AnimeEpisodesRequest((int)$malID, $currentLoop));

                    foreach ($episodesResponse->getResults() as $episodeItem) {
                        $malItem = MALAnime::query()->updateOrCreate([
                            'uniqueID' => $item['uniqueID'],
                            'notifyID' => $item['notifyID'],
                            'episode_id' => $episodeItem->getMalId(),
                        ], [
                            'title' => !empty($episodeItem->getTitle()) ? $episodeItem->getTitle() : null,
                            'title_japanese' => !empty($episodeItem->getTitleJapanese()) ? $episodeItem->getTitleJapanese() : null,
                            'title_romanji' => !empty($episodeItem->getTitleRomanji()) ? $episodeItem->getTitleRomanji() : null,
                            'aired' => !empty($episodeItem->getAired()) ? $episodeItem->getAired() : null,
                            'filler' => (int)$episodeItem->isFiller(),
                            'recap' => (int)$episodeItem->isRecap(),
                        ]);

                        $malItem->touch();
                    }
                } catch (BadResponseException|ParserException $e) {
                    $errorDetected = true;
                    $errorMessage = $e->getMessage();
                }

                $currentLoop++;

                $s_continue = true;
            }

            if ($errorDetected) {
                break;
            }
        }

        if ($errorDetected) {
            return response('Error occurred: ' . $uniqueID, 500)->header('Content-Type', 'text/plain');
        } else {
            return response('Sync successfully.', 200)->header('Content-Type', 'text/plain');
        }
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
                'service_id' => (string)$itemQuery->notifyID
            ]
        ];

        foreach ($itemQuery->mappings ?? [] as $item) {
            $filteredMappingData[] = MappingDTO::fromModel($item)->GetDTO();
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
                $filteredStudioData[] = StudioDTO::fromModel($studioQuery)->GetDTO();
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
                $filteredProducerData[] = ProducerDTO::fromModel($producerQuery)->GetDTO();
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
                $filteredLicensorData[] = LicensorDTO::fromModel($licensorQuery)->GetDTO();
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
                $filteredRelationData[] = RelationDTO::fromModel($item)->GetDTO();
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
        $characterRelationQuery = NotifyCharacterRelation::query()->where('uniqueID', $uniqueID)->first();

        if (empty($characterRelationQuery)) {
            return response('Character not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredCharacterData = [];

        foreach ($characterRelationQuery->items ?? [] as $item) {
            $characterQuery = NotifyCharacter::query()->latest()->where('notifyID', $item['characterId'])->first();

            if (!empty($characterQuery)) {
                $filteredCharacterData[] = CharacterDTO::fromModel($characterQuery)->GetDTO();
            }
        }

        return response()->json($filteredCharacterData);
    }
}
