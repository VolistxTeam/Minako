<?php

namespace App\Console\Commands;

use App\Models\NotifyAnime;
use App\Models\NotifyCharacter;
use App\Models\NotifyCharacterRelation;
use App\Models\NotifyCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DumpAnimeCommand extends Command
{
    protected $signature = "minako:dump-anime";

    protected $description = "Dump all information about anime and save to the file.";

    public function handle()
    {
        set_time_limit(0);

        $anime = NotifyAnime::all();

        $data = [];

        $totalCount = count($anime);
        $remainingCount = 0;

        foreach ($anime as $animeItem) {
            $main = $animeItem->first()->toArray();
            $episodes = $animeItem->episodes;
            $mappings = $animeItem->mappings;
            $relations = $animeItem->relations;
            $licensors = $animeItem->licensors;
            $producers = $animeItem->producers;
            $studios = $animeItem->studios;
            $trailers = $animeItem->trailers;

            $filteredCharacterData = [];

            $characterRelationQuery = NotifyCharacterRelation::query()->latest()->where('uniqueID', $animeItem->uniqueID)->first();

            if (!empty($characterRelationQuery)) {
                foreach ($characterRelationQuery->items as $item) {
                    $characterQuery = NotifyCharacter::query()->latest()->where('notifyID', $item['characterId'])->first();

                    if (!empty($characterQuery)) {
                        $filteredMappingData = [];

                        array_push($filteredMappingData, ['service' => 'notify/character', 'service_id' => (string)$characterRelationQuery->notifyID]);

                        foreach ($characterQuery->mappings as $item2) {
                            array_push($filteredMappingData, ['service' => $item2['service'], 'service_id' => $item2['serviceId']]);
                        }

                        $filteredCharacterData[] = [
                            'id' => $characterQuery->uniqueID,
                            'names' => [
                                'canonical' => $characterQuery->name_canonical,
                                'english' => $characterQuery->name_english,
                                'japanese' => $characterQuery->name_japanese,
                                'synonyms' => $characterQuery->name_synonyms
                            ],
                            'description' => $characterQuery->description,
                            'image' => [
                                'width' => $characterQuery->image_width,
                                'height' => $characterQuery->image_height,
                                'format' => 'jpg',
                                'link' => ''
                            ],
                            'attributes' => $characterQuery->attributes,
                            'mappings' => $filteredMappingData,
                            'created_at' => (string)$characterQuery->created_at,
                            'updated_at' => (string)$characterQuery->updated_at
                        ];
                    }
                }
            }

            $filteredRelationData = [];

            if (!empty($relations) && !empty($relations['items'])) {
                foreach ($relations['items'] as $item) {
                    array_push($filteredRelationData, [
                        'id' => $item['uniqueID'],
                        'type' => $item['type']
                    ]);
                }
            }

            $filteredLicensorData = [];

            if (!empty($licensors)) {
                foreach ($licensors as $item) {
                    $licensorQuery = NotifyCompany::query()->where('notifyID', $item)->first();

                    if (!empty($licensorQuery)) {
                        array_push($filteredLicensorData, [
                            'id' => $licensorQuery->uniqueID,
                            'names' => [
                                'english' => $licensorQuery->name_english,
                                'japanese' => $licensorQuery->name_japanese,
                                'synonyms' => $licensorQuery->name_synonyms
                            ],
                            'description' => $licensorQuery->description,
                            'email' => $licensorQuery->email,
                            'links' => $licensorQuery->links,
                            'created_at' => (string)$licensorQuery->created_at,
                            'updated_at' => (string)$licensorQuery->updated_at
                        ]);
                    }
                }
            }

            $filteredProducerData = [];

            if (!empty($producers)) {
                foreach ($producers as $item) {
                    $producerQuery = NotifyCompany::query()->where('notifyID', $item)->first();

                    if (!empty($producerQuery)) {
                        array_push($filteredProducerData, [
                            'id' => $producerQuery->uniqueID,
                            'names' => [
                                'english' => $producerQuery->name_english,
                                'japanese' => $producerQuery->name_japanese,
                                'synonyms' => $producerQuery->name_synonyms
                            ],
                            'description' => $producerQuery->description,
                            'email' => $producerQuery->email,
                            'links' => $producerQuery->links,
                            'created_at' => (string)$producerQuery->created_at,
                            'updated_at' => (string)$producerQuery->updated_at
                        ]);
                    }
                }
            }

            $filteredStudioData = [];

            if (!empty($studios)) {
                foreach ($studios as $item) {
                    $studioQuery = NotifyCompany::query()->where('notifyID', $item)->first();

                    if (!empty($studioQuery)) {
                        array_push($filteredStudioData, [
                            'id' => $studioQuery->uniqueID,
                            'names' => [
                                'english' => $studioQuery->name_english,
                                'japanese' => $studioQuery->name_japanese,
                                'synonyms' => $studioQuery->name_synonyms
                            ],
                            'description' => $studioQuery->description,
                            'email' => $studioQuery->email,
                            'links' => $studioQuery->links,
                            'created_at' => (string)$studioQuery->created_at,
                            'updated_at' => (string)$studioQuery->updated_at
                        ]);
                    }
                }
            }

            $filteredMappingData = [];

            array_push($filteredMappingData, ['service' => 'notify/anime', 'service_id' => (string)$main['notifyID']]);

            if (!empty($mappings)) {
                foreach ($mappings as $item) {
                    array_push($filteredMappingData, ['service' => $item['service'], 'service_id' => $item['serviceId']]);
                }
            }

            $filteredEpisodesData = [];

            if (!empty($episodes)) {
                foreach ($episodes as $episodeQuery) {
                    $filteredEpisodesData[] = [
                        'id' => $episodeQuery['id'],
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
                }
            }

            $filteredTrailersData = [];

            if (!empty($trailers)) {
                foreach ($trailers as $item) {
                    array_push($filteredTrailersData, ['service' => $item['service'], 'service_id' => $item['serviceId']]);
                }
            }

            $data[] = [
                'id' => $main['uniqueID'],
                'type' => $main['type'],
                'titles' => [
                    'english' => $main['title_english'],
                    'japanese' => $main['title_japanese'],
                    'romaji' => $main['title_romaji'],
                    'synonyms' => $main['summary']
                ],
                'canonical_title' => $main['title_canonical'],
                'synopsis' => $main['summary'],
                'status' => $main['status'],
                'genres' => $main['genres'],
                'start_date' => $main['startDate'],
                'end_date' => $main['endDate'],
                'source' => $main['source'],
                'poster_image' => [
                    'width' => $main['image_width'],
                    'height' => $main['image_height'],
                    'format' => 'jpg',
                    'link' => ''
                ],
                'rating' => [
                    'average' => !empty($main['rating_overall']) ? round($main['rating_overall'] * 10, 2) : null,
                    'story' => !empty($main['rating_story']) ? round($main['rating_story'] * 10, 2) : null,
                    'visuals' => !empty($main['rating_visuals']) ? round($main['rating_visuals'] * 10, 2) : null,
                    'soundtrack' => !empty($main['rating_soundtrack']) ? round($main['rating_soundtrack'] * 10, 2) : null,
                ],
                'first_broadcaster' => $main['firstChannel'],
                'episode_info' => [
                    'total' => $main['episodeCount'],
                    'length' => $main['episodeLength']
                ],
                'episodes' => $filteredEpisodesData,
                'characters' => $filteredCharacterData,
                'licensors' => $filteredLicensorData,
                'studios' => $filteredStudioData,
                'producers' => $filteredProducerData,
                'mappings' => $filteredMappingData,
                'trailers' => $filteredTrailersData,
                'created_at' => (string)$main['created_at'],
                'updated_at' => (string)$main['updated_at']
            ];

            $this->line('[+] Item Processed [' . $remainingCount . '/' . $totalCount . ']');
            $remainingCount++;
        }

        Storage::disk('local')->put('anime.json', json_encode([
            'build_time' => date('Y-m-d H:i:s'),
            'items' => $data
        ]));

        return 0;
    }
}
