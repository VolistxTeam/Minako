<?php

namespace App\DataTransferObjects;

use App\Facades\DTOUtils;

class Torrent extends DataTransferObjectBase
{
    public static function fromModel($torrent): self
    {
        return new self($torrent);
    }

    public function GetDTO(): array
    {
        return [
            'id' => $this->entity['uniqueID'],
            'anime_id' => optional($this->entity->anime)->uniqueID,
            'release_group' => $this->entity['releaseGroup'],
            'broadcaster' => $this->entity['broadcaster'],
            'title' => $this->entity['title'],
            'episode' => $this->entity['episode'],
            'torrent_name' => $this->entity['torrentName'],
            'info' => DTOUtils::getInfoDTO($this->entity),
            'metadata' => DTOUtils::getMetadataDTO($this->entity),
            'download' => DTOUtils::getDownloadLinksDTO($this->entity, 'ohys'),
            'created_at' => (string)$this->entity['created_at'],
            'updated_at' => (string)$this->entity['updated_at'],
        ];
    }
}
