<?php

namespace App\Helpers;

use App\DataTransferObjects\Mapping;
use App\Facades\SHA256Hasher;
use App\Models\AccessToken;
use Illuminate\Support\Str;

class DTOUtilsHelper
{
    public function getTitlesDTO($entity): array
    {
        return [
            'english' => $entity->title_english,
            'japanese' => $entity->title_japanese,
            'romaji' => $entity->title_romaji,
            'synonyms' => $entity->synonyms,
        ];
    }

    public function getPosterImageDTO($entity): array
    {
        $appUrl = config('app.url', 'http://localhost');

        return [
            'width' => $entity->image_width,
            'height' => $entity->image_height,
            'format' => 'jpg',
            'link' => "$appUrl/anime/{$entity->uniqueID}/image",
        ];
    }

    public function getRatingDTO($entity): array
    {
        return [
            'average' => $this->calculateRating($entity->rating_overall),
            'story' => $this->calculateRating($entity->rating_story),
            'visuals' => $this->calculateRating($entity->rating_visuals),
            'soundtrack' => $this->calculateRating($entity->rating_soundtrack),
        ];
    }

    public function getEpisodeInfoDTO($entity): array
    {
        return [
            'total' => $entity->episodeCount,
            'length' => $entity->episodeLength,
        ];
    }

    private function getMappingDTO($entity): array
    {
        return collect($entity->mappings ?? [])->map(function ($mapping) {
            return Mapping::fromModel($mapping)->GetDTO();
        })->prepend([
            'service' => 'notify/anime',
            'service_id' => $entity->uniqueID,
        ])->toArray();
    }

    private function getTrailersDTO($entity): array
    {
        return collect($entity->trailers ?? [])->map(function ($trailer) {
            return Mapping::fromModel($trailer)->GetDTO();
        })->toArray();
    }

    private function calculateRating($rating): ?float
    {
        return !empty($rating) ? round($rating * 10, 2) : null;
    }
}
