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
        $appUrl = config('app.url', 'http://localhost');

        return [
            'id' => $this->entity->uniqueID,
            'type' => $this->entity->type,
            'titles' => $this->formatTitles(),
            'canonical_title' => $this->entity->title_canonical,
            'synopsis' => $this->entity->summary,
            'status' => $this->entity->status,
            'genres' => $this->entity->genres,
            'start_date' => $this->entity->startDate,
            'end_date' => $this->entity->endDate,
            'source' => $this->entity->source,
            'poster_image' => $this->formatPosterImage($appUrl),
            'rating' => $this->formatRatings(),
            'first_broadcaster' => $this->entity->firstChannel,
            'episode_info' => $this->formatEpisodeInfo(),
            'mappings' => $this->formatMappings(),
            'trailers' => $this->formatTrailers(),
            'created_at' => $this->entity->created_at,
            'updated_at' => $this->entity->updated_at,
        ];
    }

    private function formatTitles(): array
    {
        return [
            'english' => $this->entity->title_english,
            'japanese' => $this->entity->title_japanese,
            'romaji' => $this->entity->title_romaji,
            'synonyms' => $this->entity->synonyms,
        ];
    }

    private function formatPosterImage($appUrl): array
    {
        return [
            'width' => $this->entity->image_width,
            'height' => $this->entity->image_height,
            'format' => 'jpg',
            'link' => "$appUrl/anime/{$this->entity->uniqueID}/image",
        ];
    }

    private function formatRatings(): array
    {
        return [
            'average' => $this->calculateRating($this->entity->rating_overall),
            'story' => $this->calculateRating($this->entity->rating_story),
            'visuals' => $this->calculateRating($this->entity->rating_visuals),
            'soundtrack' => $this->calculateRating($this->entity->rating_soundtrack),
        ];
    }

    private function calculateRating($rating): ?float
    {
        return ! empty($rating) ? round($rating * 10, 2) : null;
    }

    private function formatEpisodeInfo(): array
    {
        return [
            'total' => $this->entity->episodeCount,
            'length' => $this->entity->episodeLength,
        ];
    }

    private function formatMappings(): array
    {
        return collect($this->entity->mappings ?? [])->map(function ($mapping) {
            return Mapping::fromModel($mapping)->GetDTO();
        })->prepend([
            'service' => 'notify/anime',
            'service_id' => (string) $this->entity->uniqueID,
        ])->toArray();
    }

    private function formatTrailers(): array
    {
        return collect($this->entity->trailers ?? [])->map(function ($trailer) {
            return Mapping::fromModel($trailer)->GetDTO();
        })->toArray();
    }
}
