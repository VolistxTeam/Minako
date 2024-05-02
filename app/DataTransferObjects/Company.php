<?php

namespace App\DataTransferObjects;

use App\Facades\DTOUtils;

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
            'names' => DTOUtils::getNamesDTO($this->entity),
            'description' => $this->entity->description,
            'email' => $this->entity->email,
            'links' => $this->entity->links,
            'created_at' => $this->entity->created_at,
            'updated_at' => $this->entity->updated_at,
        ];
    }
}
