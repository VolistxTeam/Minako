<?php

namespace App\DataTransferObjects;

use App\Facades\DTOUtils;

class Anime extends DataTransferObjectBase
{
    public static function fromModel($anime): self
    {
        return new self($anime);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity->uniqueID,
            'type' => $this->entity->type,
            'titles' => DTOUtils::getTitlesDTO($this->entity),
            'canonical_title' => $this->entity->title_canonical,
            'synopsis' => $this->entity->summary,
            'status' => $this->entity->status,
            'genres' => $this->entity->genres,
            'start_date' => $this->entity->startDate,
            'end_date' => $this->entity->endDate,
            'source' => $this->entity->source,
            'poster_image' => DTOUtils::getImageDTO($this->entity, 'anime'),
            'rating' => DTOUtils::getRatingDTO($this->entity),
            'first_broadcaster' => $this->entity->firstChannel,
            'episode_info' => DTOUtils::getEpisodeInfoDTO($this->entity),
            'mappings' => DTOUtils::getMappingDTO($this->entity),
            'trailers' => DTOUtils::getTrailersDTO($this->entity),
            'created_at' => (string) $this->entity->created_at,
            'updated_at' => (string) $this->entity->updated_at,
        ];
    }
}
