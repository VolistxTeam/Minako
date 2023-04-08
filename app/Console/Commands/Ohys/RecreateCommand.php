<?php

namespace App\Console\Commands\Ohys;

use App\Classes\Torrent;
use App\Models\OhysTorrent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecreateCommand extends Command
{
    protected $signature = 'minako:ohys:recreate';

    protected $description = 'It will load all the torrents from the local and recreate the database.';

    public function handle()
    {
        set_time_limit(0);

        $allFilesList = Storage::disk('local')->allFiles('torrents');

        $totalCount = count($allFilesList);

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        foreach ($allFilesList as $file) {
            $file = basename($file);

            $torrent = new Torrent(Storage::disk('local')->path('torrents/'.$file));
            $fileBaseName = $file;

            if (self::strContains($fileBaseName, '264') && !self::strContains($fileBaseName, 'x264')) {
                $fileBaseName = str_replace('264', 'x264', $fileBaseName);
            }

            $fileBaseName = str_replace('AACx2', 'AAC', $fileBaseName);
            $fileBaseName = str_replace('x264+', 'x264', $fileBaseName);

            preg_match('/(?:\[([^\r\n]*)\][\W]?)?(?:(?:([^\r\n]+?)(?: - ([0-9.]+?(?: END)?|SP))?)[\W]?[(|[]([^\r\n(]+)? (\d+x\d+|\d{3,}[^\r\n ]*)? ([^\r\n]+)?[)\]][^.\r\n]*(?:\.([^\r\n.]*)(?:\.[\w]+)?)?)$/', $fileBaseName, $fileNameParsedArray);

            if (count($fileNameParsedArray) > 0) {
                if (empty($fileNameParsedArray[2])) {
                    continue;
                }

                $itemID = Str::random(10);
                $releaseGroup = 'Ohys-Raws';

                $title = $fileNameParsedArray[2];

                $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9.]/', '', $fileNameParsedArray[3]);

                $episode = empty($episode) ? null : $episode;

                $torrentName = $file;
                $info_totalHash = $torrent->hash_info();
                $info_totalSize = $torrent->size(2);
                $info_createdDate = date('Y-m-d H:i:s', $torrent->creation_date());
                $info_torrent_announces = $torrent->announce();
                $info_torrent_files = $torrent->content(2);
                $metadata_video_resolution = empty($fileNameParsedArray[5]) ? null : $fileNameParsedArray[5];

                $metadataCodecParsed = empty($fileNameParsedArray[6]) ? [] : explode(' ', $fileNameParsedArray[6]);

                $metadata_video_codec = empty($metadataCodecParsed[0]) ? null : $metadataCodecParsed[0];
                $metadata_audio_codec = empty($metadataCodecParsed[1]) ? null : $metadataCodecParsed[1];

                $broadcaster = empty($fileNameParsedArray[4]) ? null : $fileNameParsedArray[4];

                $hidden_download_magnet = $torrent->magnet(false);

                OhysTorrent::query()->updateOrCreate([
                    'uniqueID' => $itemID,
                ], [
                    'releaseGroup'              => $releaseGroup,
                    'broadcaster'               => $broadcaster,
                    'title'                     => $title,
                    'episode'                   => $episode,
                    'torrentName'               => $torrentName,
                    'info_totalHash'            => $info_totalHash,
                    'info_totalSize'            => $info_totalSize,
                    'info_createdDate'          => $info_createdDate,
                    'info_torrent_announces'    => $info_torrent_announces,
                    'info_torrent_files'        => $info_torrent_files,
                    'metadata_video_resolution' => $metadata_video_resolution,
                    'metadata_video_codec'      => $metadata_video_codec,
                    'metadata_audio_codec'      => $metadata_audio_codec,
                    'hidden_download_magnet'    => $hidden_download_magnet,
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();

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
