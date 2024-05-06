<?php

namespace App\DataTransferObjects;

use App\Facades\DTOUtils;

class Character extends DataTransferObjectBase
{
    public static function fromModel($character): self
    {
        return new self($character);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity->uniqueID,
            'names' => DTOUtils::getNamesDTO($this->entity),
            'description' => $this->entity->description,
            'image' => DTOUtils::getImageDTO($this->entity, 'character'),
            'attributes' => $this->entity->attributes,
            'mappings' => DTOUtils::getMappingDTO($this->entity),
            'created_at' => (string) $this->entity->created_at,
            'updated_at' => (string) $this->entity->updated_at,
        ];
    }
}
