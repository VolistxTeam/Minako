<?php

namespace App\DataTransferObjects;

use App\Facades\DTOUtils;

class Episode extends DataTransferObjectBase
{
    public static function fromModel($episode): self
    {
        return new self($episode);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity['id'],
            'episode_number' => $this->entity['episode_id'],
            'titles' => DTOUtils::getSanitizedTitlesDTO($this->entity),
            'aired' => (string) $this->entity['aired'],
            'created_at' => (string) $this->entity['created_at'],
            'updated_at' => (string) $this->entity['updated_at'],
        ];
    }
}
