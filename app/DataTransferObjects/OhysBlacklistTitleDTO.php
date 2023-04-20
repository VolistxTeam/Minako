<?php

namespace App\DataTransferObjects;

use Carbon\Carbon;

class OhysBlacklistTitleDTO extends DataTransferObjectBase
{
    public string $id;
    public string $name;
    public bool $is_active;
    public string $reason;
    public string $created_at;
    public string $updated_at;

    public static function fromModel($personal_token): self
    {
        return new self($personal_token);
    }

    public function GetDTO(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'is_active'       => $this->is_active,
            'reason'          => $this->reason,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}