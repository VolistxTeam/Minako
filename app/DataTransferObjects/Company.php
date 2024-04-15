<?php

namespace App\DataTransferObjects;

class Company extends DataTransferObjectBase
{
    public static function fromModel($company): self
    {
        return new self($company);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity->uniqueID,
            'names' => [
                'english' => $this->entity->name_english,
                'japanese' => $this->entity->name_japanese,
                'synonyms' => $this->entity->name_synonyms,
            ],
            'description' => $this->entity->description,
            'email' => $this->entity->email,
            'links' => $this->entity->links,
            'created_at' => (string) $this->entity->created_at,
            'updated_at' => (string) $this->entity->updated_at,
        ];
    }
}
