<?php

namespace App\DataTransferObjects;

class Anime extends DataTransferObjectBase
{
    public static function fromModel($anime): self
    {
        return new self($anime);
    }

    public function GetDTO(): array
    {
        //initial filtered mapping data
        $filteredMappingData = [
            [
                'service' => 'notify/anime',
                'service_id' => (string)$this->entity->uniqueID
            ]
        ];

        //append extra mappings
        foreach ($this->entity->mappings ?? [] as $mapping) {
            $filteredMappingData[] = Mapping::fromModel($mapping)->GetDTO();
        }

        //initial filtered trailer data
        $filteredTrailersData = [];
        //append extra trailers
        foreach ($this->entity->trailers ?? [] as $trailer) {
            $filteredTrailersData[] = Mapping::fromModel($trailer)->GetDTO();
        }

        return [
            'id' => $this->entity->uniqueID,
            'type' => $this->entity->type,
            'titles' => [
                'english' => $this->entity->title_english,
                'japanese' => $this->entity->title_japanese,
                'romaji' => $this->entity->title_romaji,
                'synonyms' => $this->entity->synonyms,
            ],
            'canonical_title' => $this->entity->title_canonical,
            'synopsis' => $this->entity->summary,
            'status' => $this->entity->status,
            'genres' => $this->entity->genres,
            'start_date' => $this->entity->startDate,
            'end_date' => $this->entity->endDate,
            'source' => $this->entity->source,
            'poster_image' => [
                'width' => $this->entity->image_width,
                'height' => $this->entity->image_height,
                'format' => 'jpg',
                'link' => config('app.url', 'http://localhost') . '/anime/' . $this->entity->uniqueID . '/image',
            ],
            'rating' => [
                'average' => !empty($this->entity->rating_overall) ? round($this->entity->rating_overall * 10, 2) : null,
                'story' => !empty($this->entity->rating_story) ? round($this->entity->rating_story * 10, 2) : null,
                'visuals' => !empty($this->entity->rating_visuals) ? round($this->entity->rating_visuals * 10, 2) : null,
                'soundtrack' => !empty($this->entity->rating_soundtrack) ? round($this->entity->rating_soundtrack * 10, 2) : null,
            ],
            'first_broadcaster' => $this->entity->firstChannel,
            'episode_info' => [
                'total' => $this->entity->episodeCount,
                'length' => $this->entity->episodeLength,
            ],
            'mappings' => $filteredMappingData,
            'trailers' => $filteredTrailersData,
            'created_at' => (string)$this->entity->created_at,
            'updated_at' => (string)$this->entity->updated_at,
        ];
    }
}
