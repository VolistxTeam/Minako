<?php

namespace App\Console\Commands\Ohys;

use App\Classes\AnimeSearch;
use App\Classes\Torrent;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DownloadCommand extends Command
{
    protected $signature = 'minako:ohys:download';

    protected $description = 'Check, download and parse the latest torrents using a special permission link.';

    public function handle()
    {
        set_time_limit(0);

        $faker = Factory::create();
        $animeSearchEngine = new AnimeSearch();

        $headers = [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control'   => 'max-age=0',
            'Connection'      => 'keep-alive',
            'Keep-Alive'      => '300',
            'User-Agent'      => $faker->chrome,
        ];

        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        $query = $client->get('https://ohys.nl/tt/json.php?dir=disk&p=0', ['headers' => $headers]);

        if ($query->getStatusCode() != 200) {
            $this->error('[-] Error occurred while connecting to the ohys server.');

            return 0;
        }

        $query = (string) $query->getBody();
        $decodedOhysRepo = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $query), true);

        foreach ($decodedOhysRepo as $file) {
            $torrentSearch = OhysTorrent::query()->where('torrentName', $file['t'])->first();

            if (!empty($torrentSearch)) {
                return 0;
            }

            $fp = tmpfile();
            $fpPath = stream_get_meta_data($fp)['uri'];

            $downloadStatus = $client->request('GET', 'https://ohys.nl/tt/'.$file['a'], ['headers' => $headers, 'sink' => $fpPath]);

            if ($downloadStatus->getStatusCode() != 200) {
                continue;
            }

            $torrent = new Torrent($fpPath);
            $fileBaseName = $file['t'];

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

                $itemID = substr(sha1($file['t'].'4Q84SN6cYwnz9oeL7J1k'), 0, 8);
                $releaseGroup = 'Ohys-Raws';

                $title = $fileNameParsedArray[2];
                $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9]/', '', $fileNameParsedArray[3]);

                $episode = empty($episode) ? null : $episode;

                $torrentName = $file['t'];
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

                if (Storage::disk('local')->missing('torrents/'.$file['t'])) {
                    if (file_exists($fpPath)) {
                        Storage::disk('local')->put('torrents/'.$file['t'], file_get_contents($fpPath));
                        unlink($fpPath);
                    }
                }

                $searchArray = $animeSearchEngine->SearchByTitle($title, 1);

                if (!empty($searchArray)) {
                    $anime_uniqueID = $searchArray[0]->obj['uniqueID'];

                    OhysRelation::query()->updateOrCreate([
                        'uniqueID' => $itemID,
                    ], [
                        'matchingID' => $anime_uniqueID,
                    ]);
                }

                $this->info('[Debug] Done: '.$file['t']);
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
