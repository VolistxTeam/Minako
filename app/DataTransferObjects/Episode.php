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
            'id' => $this->entity['id'],
            'episode_number' => $this->entity['episode_id'],
            'titles' => $this->formatTitles(),
            'aired' => $this->entity['aired'],
            'created_at' => (string) $this->entity['created_at'],
            'updated_at' => (string) $this->entity['updated_at'],
        ];
    }

    private function formatTitles(): array
    {
        return [
            'english' => $this->cleanTitle($this->entity['title']),
            'japanese' => $this->cleanTitle($this->entity['title_japanese']),
            'romaji' => $this->cleanTitle($this->entity['title_romanji']),
        ];
    }

    private function cleanTitle($title): ?string
    {
        // Remove UTF-8 non-breaking space and trim normal spaces
        return ! empty($title) ? trim($title, chr(0xC2).chr(0xA0)." \t\n\r\0\x0B") : null;
    }
}
