<?php

namespace App\Helpers;

use App\DataTransferObjects\Mapping;
use App\Facades\DTOUtils;

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

    public function getSanitizedTitlesDTO($entity): array
    {
        return [
            'english' => $this->sanitizeString($entity->title),
            'japanese' => $this->sanitizeString($entity->title_japanese),
            'romaji' => $this->sanitizeString($entity->title_romanji),
        ];
    }

    public function getNamesDTO($entity): array
    {
        return [
            'canonical' => $entity->name_canonical,
            'english' => $entity->name_english,
            'japanese' => $entity->name_japanese,
            'synonyms' => $entity->name_synonyms,
        ];
    }

    public function getImageDTO($entity, $key): array
    {
        $appUrl = config('app.url', 'http://localhost');

        return [
            'width' => $entity->image_width,
            'height' => $entity->image_height,
            'format' => 'jpg',
            'link' => "$appUrl/$key/{$entity->uniqueID}/image",
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

    public function getMappingDTO($entity): array
    {
        return collect($entity->mappings ?? [])->map(function ($mapping) {
            return Mapping::fromModel($mapping)->GetDTO();
        })->prepend([
            'service' => 'notify/anime',
            'service_id' => $entity->uniqueID,
        ])->toArray();
    }

    public function getTrailersDTO($entity): array
    {
        return collect($entity->trailers ?? [])->map(function ($trailer) {
            return Mapping::fromModel($trailer)->GetDTO();
        })->toArray();
    }

    public function getInfoDTO($entity): array
    {
        return [
            'hash' => $entity->info_totalHash,
            'size' => $entity->info_totalSize,
            'created_at' => $entity->info_createdDate,
            'announces' => DTOUtils::getAnnouncesDTO($entity),
            'files' => $entity->info_torrent_files,
        ];
    }

    public function getAnnouncesDTO($entity): array
    {
        return collect($entity->info_torrent_announces)
            ->filter(function ($item) {
                return count($item) > 0;
            })
            ->pluck(0)
            ->toArray();
    }

    public function getMetadataDTO($entity): array
    {
        return [
            'video' => [
                'codec' => $entity->metadata_video_codec,
                'resolution' => $entity->metadata_video_resolution,
            ],
            'audio' => [
                'codec' => $entity->metadata_audio_codec,
            ],
        ];
    }

    public function getDownloadLinksDTO($entity, $key): array
    {
        $appUrl = config('app.url', 'http://localhost');

        return [
            'torrent' => "$appUrl/$key/$entity->uniqueID/download?type=torrent",
            'magnet' => "$appUrl/$key/$entity->uniqueID/download?type=magnet",
        ];
    }

    private function calculateRating($rating): ?float
    {
        return ! empty($rating) ? round($rating * 10, 2) : null;
    }

    private function sanitizeString($string): ?string
    {
        // Remove UTF-8 non-breaking space and trim normal spaces
        return ! empty($string) ? trim($string, chr(0xC2).chr(0xA0)." \t\n\r\0\x0B") : null;
    }
}
