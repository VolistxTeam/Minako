<?php

namespace App\DataTransferObjects;

class OhysBlacklist extends DataTransferObjectBase
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
            'id' => $this->id,
            'name' => $this->name,
            'reason' => $this->reason,
            'is_active' => $this->is_active,
            'created_at' => (string)$this->created_at,
            'updated_at' => (string)$this->updated_at,
        ];
    }
}
