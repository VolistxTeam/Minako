<?php

namespace App\DataTransferObjects;

class RelationDTO extends DataTransferObjectBase
{
    public static function fromModel($relation): self
    {
        return new self($relation);
    }

    public function GetDTO(): array
    {
       return [
           'id' => $this->entity['uniqueID'],
           'type' => $this->entity['type']
       ];
    }
}
