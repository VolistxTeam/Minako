<?php

namespace App\DataTransferObjects;

class EpisodeInformationDTO extends DataTransferObjectBase
{
    public static function fromModel($episode): self
    {
        return new self($episode);
    }

    public function GetDTO(): array
    {
        return  [
            'id'       => $this->entity['id'],
            'anime_id' => $this->entity['uniqueID'],
            'title'    => $this->entity['title'],
        ];
    }


}
