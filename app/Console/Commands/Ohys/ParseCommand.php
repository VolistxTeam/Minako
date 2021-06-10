<?php

namespace App\Console\Commands\Ohys;

use App\Classes\Torrent;
use App\Models\OhysTorrent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ParseCommand extends Command
{
    protected $signature = "minako:ohys:parse";

    protected $description = "Parse and update all torrents in the storage.";

    public function handle()
    {
        set_time_limit(0);

        $allFiles = Storage::disk('local')->allFiles('minako/ohys-torrents');

        foreach ($allFiles as $file) {
            $fileFullPath = Storage::disk('local')->path($file);
            $fileBaseName = basename($fileFullPath);

            $torrentSearch = OhysTorrent::query()->where('torrentName', $fileBaseName)->first();

            if (!empty($torrentSearch)) {
                $this->error('[Skipping] The item exists in the database.');
                continue;
            }

            $torrent = new Torrent($fileFullPath);

            if (self::strContains($fileBaseName, '264') && !self::strContains($fileBaseName, 'x264')) {
                $fileBaseName = str_replace('264', 'x264', $fileBaseName);
            }

            $fileBaseName = str_replace('AACx2', 'AAC', $fileBaseName);

            preg_match('/(?:\[([^\r\n]*)\][\W]?)?(?:(?:([^\r\n]+?)(?: - ([0-9.]+?(?: END)?|SP))?)[\W]?[(|[]([^\r\n(]+)? (\d+x\d+|\d{3,}[^\r\n ]*)? ([^\r\n]+)?[)\]][^.\r\n]*(?:\.([^\r\n.]*)(?:\.[\w]+)?)?)$/', $fileBaseName, $fileNameParsedArray);

            if (count($fileNameParsedArray) > 0) {
                if (empty($fileNameParsedArray[2])) {
                    $this->line('Not Found. Continue...');
                    continue;
                }

                $itemID = substr(sha1($fileBaseName . '4Q84SN6cYwnz9oeL7J1k'), 0, 8);
                $releaseGroup = 'Ohys-Raws';

                $title = $fileNameParsedArray[2];
                $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9]/', '', $fileNameParsedArray[3]);

                $episode = empty($episode) ? null : $episode;

                $torrentName = $fileBaseName;
                $info_totalHash = $torrent->hash_info();
                $info_totalSize = $torrent->size(2);
                $info_createdDate = date('Y-m-d H:i:s', $torrent->creation_date());
                $info_torrent_announces = $torrent->announce();
                $info_torrent_files = $torrent->content(2);
                $metadata_video_resolution = empty($fileNameParsedArray[5]) ? null : $fileNameParsedArray[5];

                $metadataCodecParsed = empty($fileNameParsedArray[6]) ? array() : explode(" ", $fileNameParsedArray[6]);

                $metadata_video_codec = empty($metadataCodecParsed[0]) ? null : $metadataCodecParsed[0];
                $metadata_audio_codec = empty($metadataCodecParsed[1]) ? null : $metadataCodecParsed[1];
                $hidden_download_magnet = $torrent->magnet(false);

                OhysTorrent::query()->updateOrCreate([
                    'uniqueID' => $itemID
                ], [
                    'releaseGroup' => $releaseGroup,
                    'title' => $title,
                    'episode' => $episode,
                    'torrentName' => $torrentName,
                    'info_totalHash' => $info_totalHash,
                    'info_totalSize' => $info_totalSize,
                    'info_createdDate' => $info_createdDate,
                    'info_torrent_announces' => $info_torrent_announces,
                    'info_torrent_files' => $info_torrent_files,
                    'metadata_video_resolution' => $metadata_video_resolution,
                    'metadata_video_codec' => $metadata_video_codec,
                    'metadata_audio_codec' => $metadata_audio_codec,
                    'hidden_download_magnet' => $hidden_download_magnet
                ]);

                $this->info('[Debug] Done: ' . $fileBaseName);
            } else {
                $this->line('Not Found. Continue...');
            }
        }

        return 0;
    }

    private function strContains($source, $whereStr): bool
    {
        if (str_contains($source, $whereStr)) {
            return true;
        } else {
            return false;
        }
    }
}
