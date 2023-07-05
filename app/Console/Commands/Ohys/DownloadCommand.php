<?php

namespace App\Console\Commands\Ohys;

use App\Classes\Torrent;
use App\Jobs\OhysRelationJob;
use App\Models\OhysTorrent;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class DownloadCommand extends Command
{
    protected $signature = 'minako:ohys:download';
    protected $description = 'Check, download and parse the latest torrents using a special permission link.';

    public function handle()
    {
        set_time_limit(0);

        $headers = $this->getHeaders();
        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $response = $client->get('https://ohys.nl/tt/json.php?dir=disk&p=0', ['headers' => $headers]);

        if ($response->getStatusCode() != 200) {
            $this->error('[-] Error occurred while connecting to the ohys server.');

            return 0;
        }

        $decodedOhysRepo = $this->getDecodedOhysRepo($response);

        foreach ($decodedOhysRepo as $file) {
            if (OhysTorrent::query()->where('torrentName', $file['t'])->exists()) {
                return 0;
            }

            $tempFile = $temporaryDirectory->path($file['t']);

            try {
                $response = $client->request('GET', 'https://ohys.nl/tt/' . $file['a'], ['headers' => $headers, 'sink' => $tempFile]);
            } catch (GuzzleException $e) {
                $this->line('Not Found. Continue...');
                continue;
            }

            $fileNameParsedArray = $this->parseFileName($file);
            if (count($fileNameParsedArray) === 0 || empty($fileNameParsedArray[2])) {
                $this->line('Not Found. Continue...');
                continue;
            }

            $torrentData = $this->extractTorrentData($tempFile, $file, $fileNameParsedArray);

            File::copy($tempFile, __DIR__. '/../../../../storage/app/torrents/'.$torrentData['torrentName']);
            $createdInfo = OhysTorrent::query()->updateOrCreate(['uniqueID' => $torrentData['uniqueID']], $torrentData);

            $createdInfo->touch();

            dispatch(new OhysRelationJob($createdInfo));

            $this->info('[Debug] Done: '.$file['t']);
        }

        $temporaryDirectory->delete();

        return 0;
    }

    private function getHeaders()
    {
        $faker = Factory::create();

        return [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control'   => 'max-age=0',
            'Connection'      => 'keep-alive',
            'Keep-Alive'      => '300',
            'User-Agent'      => $faker->chrome,
        ];
    }

    private function getDecodedOhysRepo($response)
    {
        $responseBody = (string) $response->getBody();

        return json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $responseBody), true);
    }

    private function parseFileName($file)
    {
        $fileBaseName = str_replace(['AACx2'], ['AAC'], $file['t']);
        preg_match('/(?:\[([^\r\n]*)\][\W]?)?(?:(?:([^\r\n]+?)(?: - ([0-9.]+?(?: END)?|SP))?)[\W]?[(|[]([^\r\n(]+)? (\d+x\d+|\d{3,}[^\r\n ]*)? ([^\r\n]+)?[)\]][^.\r\n]*(?:\.([^\r\n.]*)(?:\.[\w]+)?)?)$/', $fileBaseName, $fileNameParsedArray);

        return $fileNameParsedArray;
    }

    private function extractTorrentData($torrent, $file, $fileNameParsedArray)
    {
        $torrent = new Torrent($torrent);

        $itemID = Str::random(10);
        $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9]/', '', $fileNameParsedArray[3]);
        $episode = empty($episode) ? null : $episode;

        $metadataCodecParsed = empty($fileNameParsedArray[6]) ? [] : explode(' ', $fileNameParsedArray[6]);

        if ($metadataCodecParsed[0] == '2x') {
            array_shift($metadataCodecParsed);
        }

        if ($metadataCodecParsed[0] == '264') {
            $metadataCodecParsed[0] = 'x264';
        }

        return [
            'uniqueID'                  => $itemID,
            'releaseGroup'              => 'Ohys-Raws',
            'broadcaster'               => $fileNameParsedArray[4] ?? null,
            'title'                     => $fileNameParsedArray[2],
            'episode'                   => $episode,
            'torrentName'               => $file['t'],
            'info_totalHash'            => $torrent->hash_info(),
            'info_totalSize'            => $torrent->size(2),
            'info_createdDate'          => date('Y-m-d H:i:s', $torrent->creation_date()),
            'info_torrent_announces'    => $torrent->announce(),
            'info_torrent_files'        => $torrent->content(2),
            'metadata_video_resolution' => $fileNameParsedArray[5] ?? null,
            'metadata_video_codec'      => $metadataCodecParsed[0] ?? null,
            'metadata_audio_codec'      => $metadataCodecParsed[1] ?? null,
            'hidden_download_magnet'    => $torrent->magnet(false),
        ];
    }
}
