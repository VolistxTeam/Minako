<?php

namespace App\DataTransferObjects;

class Company extends DataTransferObjectBase
{
    public static function fromModel($company): self
    {
        return new self($company);
    }

    public function getDTO(): array
    {
        return [
            'id' => $this->entity->uniqueID,
            'names' => $this->formatNames(),
            'description' => $this->entity->description,
            'email' => $this->entity->email,
            'links' => $this->entity->links,
            'created_at' => $this->entity->created_at,
            'updated_at' => $this->entity->updated_at,
        ];
    }

    private function formatNames(): array
    {
        return [
            'english' => $this->entity->name_english,
            'japanese' => $this->entity->name_japanese,
            'synonyms' => $this->entity->name_synonyms,
        ];
    }
}
