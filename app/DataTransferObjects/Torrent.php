<?php

namespace App\DataTransferObjects;

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
            'info' => $this->formatInfo(),
            'metadata' => $this->formatMetadata(),
            'download' => $this->formatDownloadLinks(),
            'created_at' => (string) $this->entity['created_at'],
            'updated_at' => (string) $this->entity['updated_at'],
        ];
    }

    private function formatInfo(): array
    {
        return [
            'hash' => $this->entity['info_totalHash'],
            'size' => $this->entity['info_totalSize'],
            'created_at' => (string) $this->entity['info_createdDate'],
            'announces' => $this->getAnnounces(),
            'files' => $this->entity['info_torrent_files'],
        ];
    }

    private function getAnnounces(): array
    {
        return collect($this->entity['info_torrent_announces'])
            ->filter(function ($item) {
                return count($item) > 0;
            })
            ->pluck(0)
            ->toArray();
    }

    private function formatMetadata(): array
    {
        return [
            'video' => [
                'codec' => $this->entity['metadata_video_codec'],
                'resolution' => $this->entity['metadata_video_resolution'],
            ],
            'audio' => [
                'codec' => $this->entity['metadata_audio_codec'],
            ],
        ];
    }

    private function formatDownloadLinks(): array
    {
        $appUrl = config('app.url', 'http://localhost');

        return [
            'torrent' => "$appUrl/ohys/{$this->entity['uniqueID']}/download?type=torrent",
            'magnet' => "$appUrl/ohys/{$this->entity['uniqueID']}/download?type=magnet",
        ];
    }
}
