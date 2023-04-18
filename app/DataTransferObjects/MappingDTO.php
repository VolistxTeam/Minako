<?php

namespace App\DataTransferObjects;

class MappingDTO extends DataTransferObjectBase
{
    public static function fromModel($mapping): self
    {
        return new self($mapping);
    }

    public function GetDTO(): array
    {
        return [
            'service' => $this->entity['service'],
            'service_id' => $this->entity['serviceId']
        ];
    }


}
