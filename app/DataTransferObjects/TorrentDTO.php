<?php

namespace App\DataTransferObjects;

class TorrentDTO extends DataTransferObjectBase
{
    public static function fromModel($torrent): self
    {
        return new self($torrent);
    }

    public function GetDTO(): array
    {
        $announcesRebuild = collect($this->entity['info_torrent_announces'])->filter(function ($item) {
            return count($item) > 0;
        })->pluck(0)->toArray();

        $appUrl = config('app.url', 'http://localhost');

        return [
            'id'            => $this->entity['uniqueID'],
            'anime_id'      => optional($this->entity->anime)->uniqueID,
            'release_group' => $this->entity['releaseGroup'],
            'broadcaster'   => $this->entity['broadcaster'],
            'title'         => $this->entity['title'],
            'episode'       => $this->entity['episode'],
            'torrent_name'  => $this->entity['torrentName'],
            'info'          => [
                'hash'       => $this->entity['info_totalHash'],
                'size'       => $this->entity['info_totalSize'],
                'created_at' => $this->entity['info_createdDate'],
                'announces'  => $announcesRebuild,
                'files'      => $this->entity['info_torrent_files'],
            ],
            'metadata' => [
                'video' => [
                    'codec'      => $this->entity['metadata_video_codec'],
                    'resolution' => $this->entity['metadata_video_resolution'],
                ],
                'audio' => [
                    'codec' => $this->entity['metadata_audio_codec'],
                ],
            ],
            'download' => [
                'official' => [
                    'torrent' => 'https://ohys.nl/tt/disk/'.$this->entity['torrentName'],
                ],
                'mirror' => [
                    'torrent' => $appUrl.'/ohys/'.$this->entity['uniqueID'].'/download?type=torrent',
                    'magnet'  => $appUrl.'/ohys/'.$this->entity['uniqueID'].'/download?type=magnet',
                ],
            ],
        ];
    }


}
