<?php

namespace App\DataTransferObjects;

class Episode extends DataTransferObjectBase
{
    public static function fromModel($episode): self
    {
        return new self($episode);
    }

    public function GetDTO(): array
    {
        return [
            'id'             => $this->entity['id'],
            'episode_number' => $this->entity['episode_id'],
            'titles'         => [
                'english'  => !empty($this->entity['title']) ? trim($this->entity['title'], chr(0xC2).chr(0xA0)) : null,
                'japanese' => !empty($this->entity['title_japanese']) ? trim($this->entity['title_japanese'], chr(0xC2).chr(0xA0)) : null,
                'romaji'   => !empty($this->entity['title_romanji']) ? trim($this->entity['title_romanji'], chr(0xC2).chr(0xA0)) : null,
            ],
            'aired'      => (string) $this->entity['aired'],
            'created_at' => (string) $this->entity['created_at'],
            'updated_at' => (string) $this->entity['updated_at'],
        ];
    }
}
