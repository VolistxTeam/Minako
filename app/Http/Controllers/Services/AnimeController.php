<?php

namespace App\Http\Controllers\Services;

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

class AnimeController extends Controller
{
    public function Search(Request $request, $name)
    {
        $requestType = $request->input('type');

        $name = urldecode($name);

        $name = $this->escapeElasticReservedChars($name);

        if (empty($requestType)) {
            $searchQuery = NotifyAnime::query()
                ->where('title_canonical', 'LIKE', "%$name%")
                ->orWhere('title_romaji', 'LIKE', "%$name%")
                ->orWhere('title_english', 'LIKE', "%$name%")
                ->orWhere('title_japanese', 'LIKE', "%$name%")
                ->orWhere('title_hiragana', 'LIKE', "%$name%")
                ->orWhereJsonContains('title_synonyms', $name)
                ->take(100)
                ->paginate(50, ['*'], 'page', 1);
        } else {
            $searchQuery = NotifyAnime::query()
                ->where('type', strtolower($requestType))
                ->where('title_canonical', 'LIKE', "%$name%")
                ->orWhere('title_romaji', 'LIKE', "%$name%")
                ->orWhere('title_english', 'LIKE', "%$name%")
                ->orWhere('title_japanese', 'LIKE', "%$name%")
                ->orWhere('title_hiragana', 'LIKE', "%$name%")
                ->orWhereJsonContains('title_synonyms', $name)
                ->take(100)
                ->paginate(50, ['*'], 'page', 1);
        }

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = [];
            $newArray['id'] = $item['uniqueID'];
            $newArray['type'] = $item['type'];
            $newArray['title'] = $item['title_canonical'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetItem($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [];

        $filteredMappingData[] = ['service' => 'notify/anime', 'service_id' => (string) $itemQuery->notifyID];

        if (is_array($itemQuery->mappings)) {
            foreach ($itemQuery->mappings as $item) {
                $filteredMappingData[] = ['service' => $item['service'], 'service_id' => $item['serviceId']];
            }
        }

        $filteredTrailersData = [];

        if (is_array($itemQuery->trailers)) {
            foreach ($itemQuery->trailers as $item) {
                $filteredTrailersData[] = ['service' => $item['service'], 'service_id' => $item['serviceId']];
            }
        }

        $buildResponse = [
            'id'     => $itemQuery['uniqueID'],
            'type'   => $itemQuery['type'],
            'titles' => [
                'english'  => $itemQuery['title_english'],
                'japanese' => $itemQuery['title_japanese'],
                'romaji'   => $itemQuery['title_romaji'],
                'synonyms' => $itemQuery['synonyms'],
            ],
            'canonical_title' => $itemQuery['title_canonical'],
            'synopsis'        => $itemQuery['summary'],
            'status'          => $itemQuery['status'],
            'genres'          => $itemQuery['genres'],
            'start_date'      => $itemQuery['startDate'],
            'end_date'        => $itemQuery['endDate'],
            'source'          => $itemQuery['source'],
            'poster_image'    => [
                'width'  => $itemQuery['image_width'],
                'height' => $itemQuery['image_height'],
                'format' => 'jpg',
                'link'   => env('APP_URL', 'http://localhost').'/anime/'.$itemQuery['uniqueID'].'/image',
            ],
            'rating' => [
                'average'    => !empty($itemQuery['rating_overall']) ? round($itemQuery['rating_overall'] * 10, 2) : null,
                'story'      => !empty($itemQuery['rating_story']) ? round($itemQuery['rating_story'] * 10, 2) : null,
                'visuals'    => !empty($itemQuery['rating_visuals']) ? round($itemQuery['rating_visuals'] * 10, 2) : null,
                'soundtrack' => !empty($itemQuery['rating_soundtrack']) ? round($itemQuery['rating_soundtrack'] * 10, 2) : null,
            ],
            'first_broadcaster' => $itemQuery['firstChannel'],
            'episode_info'      => [
                'total'  => $itemQuery['episodeCount'],
                'length' => $itemQuery['episodeLength'],
            ],
            'mappings'   => $filteredMappingData,
            'trailers'   => $filteredTrailersData,
            'created_at' => (string) $itemQuery['created_at'],
            'updated_at' => (string) $itemQuery['updated_at'],
        ];

        return response()->json($buildResponse);
    }

    public function GetImage($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $id = $itemQuery->uniqueID;

        $contents = Storage::disk('local')->get('posters/'.$id.'.jpg');

        if (empty($contents)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        return Response::make($contents, 200)->header('Content-Type', 'image/jpeg');
    }

    public function GetEpisode($uniqueID, $episodeNumber)
    {
        $itemQuery = NotifyAnime::query()->latest()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $episodeQuery = $itemQuery->episodes->where('episode_id', $episodeNumber)->first();

        if (empty($episodeQuery)) {
            return response('Episode not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $buildResponse = [
            'id'             => $episodeQuery['id'],
            'episode_number' => $episodeQuery['episode_id'],
            'titles'         => [
                'english'  => !empty($episodeQuery['title']) ? trim($episodeQuery['title'], chr(0xC2).chr(0xA0)) : null,
                'japanese' => !empty($episodeQuery['title_japanese']) ? trim($episodeQuery['title_japanese'], chr(0xC2).chr(0xA0)) : null,
                'romaji'   => !empty($episodeQuery['title_romanji']) ? trim($episodeQuery['title_romanji'], chr(0xC2).chr(0xA0)) : null,
            ],
            'aired'      => (string) $episodeQuery['aired'],
            'created_at' => (string) $episodeQuery['created_at'],
            'updated_at' => (string) $episodeQuery['updated_at'],
        ];

        return response()->json($buildResponse);
    }

    public function GetEpisodes(Request $request, $uniqueID)
    {
        $paginateNumber = $request->input('p', 1);

        if (filter_var($paginateNumber, FILTER_VALIDATE_INT) === false) {
            $paginateNumber = 1;
        }

        $searchQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($searchQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        if (empty($searchQuery->episodes)) {
            return response('Episodes not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $episodesQuery = self::customPaginate($searchQuery->episodes, 15, $paginateNumber);

        $itemsFiltered = [];

        foreach ($episodesQuery as $episodeQuery) {
            $itemsFiltered[] = [
                'id'             => $episodeQuery['id'],
                'episode_number' => $episodeQuery['episode_id'],
                'titles'         => [
                    'english'  => !empty($episodeQuery['title']) ? trim($episodeQuery['title'], chr(0xC2).chr(0xA0)) : null,
                    'japanese' => !empty($episodeQuery['title_japanese']) ? trim($episodeQuery['title_japanese'], chr(0xC2).chr(0xA0)) : null,
                    'romaji'   => !empty($episodeQuery['title_romanji']) ? trim($episodeQuery['title_romanji'], chr(0xC2).chr(0xA0)) : null,
                ],
                'aired'      => (string) $episodeQuery['aired'],
                'created_at' => (string) $episodeQuery['created_at'],
                'updated_at' => (string) $episodeQuery['updated_at'],
            ];
        }

        $buildResponse = [
            'pagination' => [
                'per_page' => $episodesQuery->perPage(),
                'current'  => $episodesQuery->currentPage(),
                'total'    => $episodesQuery->lastPage(),
            ],
            'items' => $itemsFiltered,
        ];

        return response()->json($buildResponse);
    }

    public function SyncEpisodes(Request $request, $uniqueID)
    {
        $jikan = new MalClient();

        $item = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (($item['type'] == 'movie' || $item['type'] == 'music') && $item['episodeCount'] >= 2 && !is_array($item['mappings'])) {
            return response('Not supported type: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $malID = '';

        foreach ($item['mappings'] as $value) {
            if ($value['service'] == 'myanimelist/anime') {
                $malID = $value['serviceId'];
            }
        }

        if (empty($malID) || filter_var($malID, FILTER_VALIDATE_INT) === false) {
            return response('No MAL ID found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $pageNumber = 1;
        $currentLoop = 1;
        $errorDetected = false;
        $errorMessage = '';

        while ($currentLoop <= $pageNumber) {
            $s_continue = false;

            while (!$s_continue) {
                try {
                    $episodesResponse = $jikan->getAnimeEpisodes(new \Jikan\Request\Anime\AnimeEpisodesRequest((int) $malID, $currentLoop));

                    foreach ($episodesResponse->getResults() as $episodeItem) {
                        $malItem = MALAnime::query()->updateOrCreate([
                            'uniqueID'   => $item['uniqueID'],
                            'notifyID'   => $item['notifyID'],
                            'episode_id' => $episodeItem->getMalId(),
                        ], [
                            'title'          => !empty($episodeItem->getTitle()) ? $episodeItem->getTitle() : null,
                            'title_japanese' => !empty($episodeItem->getTitleJapanese()) ? $episodeItem->getTitleJapanese() : null,
                            'title_romanji'  => !empty($episodeItem->getTitleRomanji()) ? $episodeItem->getTitleRomanji() : null,
                            'aired'          => !empty($episodeItem->getAired()) ? $episodeItem->getAired() : null,
                            'filler'         => (int) $episodeItem->isFiller(),
                            'recap'          => (int) $episodeItem->isRecap(),
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
            return response('Error occurred: '.$uniqueID, 500)->header('Content-Type', 'text/plain');
        } else {
            return response('Sync successfully.', 200)->header('Content-Type', 'text/plain');
        }
    }

    public function GetMappings($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [];

        $filteredMappingData[] = ['service' => 'notify/anime', 'service_id' => (string) $itemQuery->notifyID];

        if (is_array($itemQuery->mappings)) {
            foreach ($itemQuery->mappings as $item) {
                $filteredMappingData[] = ['service' => $item['service'], 'service_id' => $item['serviceId']];
            }
        }

        $buildResponse = [
            'id'       => $itemQuery['uniqueID'],
            'mappings' => $filteredMappingData,
        ];

        return response()->json($buildResponse);
    }

    public function GetStudios($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredStudioData = [];

        foreach ($itemQuery->studios as $item) {
            $studioQuery = NotifyCompany::query()->where('notifyID', $item)->first();

            if (!empty($studioQuery)) {
                $filteredStudioData[] = [
                    'id'    => $studioQuery->uniqueID,
                    'names' => [
                        'english'  => $studioQuery->name_english,
                        'japanese' => $studioQuery->name_japanese,
                        'synonyms' => $studioQuery->name_synonyms,
                    ],
                    'description' => $studioQuery->description,
                    'email'       => $studioQuery->email,
                    'links'       => $studioQuery->links,
                    'created_at'  => (string) $studioQuery->created_at,
                    'updated_at'  => (string) $studioQuery->updated_at,
                ];
            }
        }

        $buildResponse = [
            'id'      => $itemQuery['uniqueID'],
            'studios' => $filteredStudioData,
        ];

        return response()->json($buildResponse);
    }

    public function GetProducers($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredProducerData = [];

        if (is_array($itemQuery->producers)) {
            foreach ($itemQuery->producers as $item) {
                $producerQuery = NotifyCompany::query()->latest()->where('notifyID', $item)->first();

                if (!empty($producerQuery)) {
                    $filteredProducerData[] = [
                        'id'    => $producerQuery->uniqueID,
                        'names' => [
                            'english'  => $producerQuery->name_english,
                            'japanese' => $producerQuery->name_japanese,
                            'synonyms' => $producerQuery->name_synonyms,
                        ],
                        'description' => $producerQuery->description,
                        'email'       => $producerQuery->email,
                        'links'       => $producerQuery->links,
                        'created_at'  => (string) $producerQuery->created_at,
                        'updated_at'  => (string) $producerQuery->updated_at,
                    ];
                }
            }
        }

        $buildResponse = [
            'id'        => $itemQuery['uniqueID'],
            'producers' => $filteredProducerData,
        ];

        return response()->json($buildResponse);
    }

    public function GetLicensors($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredLicensorData = [];

        if (!empty($itemQuery->licensors)) {
            foreach ($itemQuery->licensors as $item) {
                $licensorQuery = NotifyCompany::query()->where('notifyID', $item)->first();

                if (!empty($licensorQuery)) {
                    $filteredLicensorData[] = [
                        'id'    => $licensorQuery->uniqueID,
                        'names' => [
                            'english'  => $licensorQuery->name_english,
                            'japanese' => $licensorQuery->name_japanese,
                            'synonyms' => $licensorQuery->name_synonyms,
                        ],
                        'description' => $licensorQuery->description,
                        'email'       => $licensorQuery->email,
                        'links'       => $licensorQuery->links,
                        'created_at'  => (string) $licensorQuery->created_at,
                        'updated_at'  => (string) $licensorQuery->updated_at,
                    ];
                }
            }
        }

        $buildResponse = [
            'id'        => $itemQuery['uniqueID'],
            'licensors' => $filteredLicensorData,
        ];

        return response()->json($buildResponse);
    }

    public function GetRelations($uniqueID)
    {
        $itemQuery = NotifyAnime::query()->where('uniqueID', $uniqueID)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredRelationData = [];

        $itemArray = $itemQuery->relations;

        if (!empty($itemArray) && !empty($itemArray['items'])) {
            foreach ($itemArray['items'] as $item) {
                $filteredRelationData[] = [
                    'id'   => $item['uniqueID'],
                    'type' => $item['type'],
                ];
            }
        }

        $buildResponse = [
            'id'        => $itemQuery['uniqueID'],
            'relations' => $filteredRelationData,
        ];

        return response()->json($buildResponse);
    }

    public function GetCharacters($uniqueID)
    {
        $characterRelationQuery = NotifyCharacterRelation::query()->where('uniqueID', $uniqueID)->first();

        if (empty($characterRelationQuery)) {
            return response('Character not found: '.$uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $filteredCharacterData = [];

        if (is_array($characterRelationQuery->items)) {
            foreach ($characterRelationQuery->items as $item) {
                $characterQuery = NotifyCharacter::query()->latest()->where('notifyID', $item['characterId'])->first();

                if (!empty($characterQuery)) {
                    $filteredMappingData = [];

                    $filteredMappingData[] = ['service' => 'notify/character', 'service_id' => (string) $characterRelationQuery->notifyID];

                    foreach ($characterQuery->mappings as $item2) {
                        $filteredMappingData[] = ['service' => $item2['service'], 'service_id' => $item2['serviceId']];
                    }

                    $filteredCharacterData[] = [
                        'id'    => $characterQuery->uniqueID,
                        'names' => [
                            'canonical' => $characterQuery->name_canonical,
                            'english'   => $characterQuery->name_english,
                            'japanese'  => $characterQuery->name_japanese,
                            'synonyms'  => $characterQuery->name_synonyms,
                        ],
                        'description' => $characterQuery->description,
                        'image'       => [
                            'width'  => $characterQuery->image_width,
                            'height' => $characterQuery->image_height,
                            'format' => 'jpg',
                            'link'   => env('APP_URL', 'http://localhost').'/character/'.$characterQuery->uniqueID.'/image',
                        ],
                        'attributes' => $characterQuery->attributes,
                        'mappings'   => $filteredMappingData,
                        'created_at' => (string) $characterQuery->created_at,
                        'updated_at' => (string) $characterQuery->updated_at,
                    ];
                }
            }
        }

        return response()->json($filteredCharacterData);
    }
}
