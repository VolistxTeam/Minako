<?php

namespace App\Console\Commands\Ohys;

use App\Classes\Torrent;
use App\Helpers\NyaaCrawler;
use App\Models\NotifyAnime;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class DownloadCommand extends Command
{
    protected $signature = 'minako:ohys:download';
    protected $description = 'Check, download and parse the latest torrents using a special permission link.';

    public function handle()
    {
//        set_time_limit(0);
//
//        $directoryPath = 'torrents_old_2';
//        $files = Storage::disk('local')->files($directoryPath);
//
//        $this->components->info('Processing Torrents from local storage...');
//
//        foreach ($files as $file) {
//            $filePath = storage_path('app/' . $file);
//            $torrentFileName = basename($filePath);
//
//            if (OhysTorrent::query()->where('torrentName', $torrentFileName)->exists()) {
//                continue;
//            }
//
//            $fileNameParsedArray = $this->parseFileName($torrentFileName);
//            if (count($fileNameParsedArray) === 0 || empty($fileNameParsedArray[2])) {
//                $this->line('Not Found. Continue...');
//                continue;
//            }
//
//            $torrentData = $this->extractTorrentData($filePath, $torrentFileName, $fileNameParsedArray);
//
//            $storageDirectory = 'torrents';
//            if (!Storage::disk('local')->exists($storageDirectory)) {
//                Storage::disk('local')->makeDirectory($storageDirectory);
//            }
//
//            // Optionally move file to new directory if needed
//            Storage::disk('local')->move($file, $storageDirectory.'/'.$torrentFileName);
//
//            $createdInfo = OhysTorrent::query()->updateOrCreate(['uniqueID' => $torrentData['uniqueID']], $torrentData);
//
//            $createdInfo->touch();
//
//            $this->info('[Debug] Done: ' . $torrentFileName);
//        }
//
//        $this->info('All files processed.');
//
//        return 0;

        set_time_limit(0);

        $headers = $this->getHeaders();
        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);
        $temporaryDirectory = (new TemporaryDirectory())->force()->create();

        $response = $client->get('https://nyaa.si', ['headers' => $headers]);

        if ($response->getStatusCode() != 200) {
            $this->error('[-] Error occurred while connecting to the nyaa.si server.');

            return 0;
        }

        $this->components->info('Downloading All Ohys-Raws Torrents...');
        $nyaaCrawler = new NyaaCrawler();
        $allTorrents = $nyaaCrawler->getTorrents();

        ray(count($allTorrents));

        $this->components->info('Processing Torrents...');
        foreach ($allTorrents as $torrent) {
            if (OhysTorrent::query()->where('torrentName', $torrent['title'] . '.torrent')->exists()) {
                continue;
            }

            $tempFile = $temporaryDirectory->path($torrent['title'] . '.torrent');

            try {
                $response = $client->request('GET', $torrent['downloads']['url'], ['headers' => $headers, 'sink' => $tempFile]);
            } catch (GuzzleException $e) {
                $this->line('Not Found. Continue...');
                continue;
            }

            $fileNameParsedArray = $this->parseFileName($torrent['title'] . '.torrent');
            if (count($fileNameParsedArray) === 0 || empty($fileNameParsedArray[2])) {
                $this->line('Not Found. Continue...');
                continue;
            }

            $torrentData = $this->extractTorrentData($tempFile, $torrent['title'], $fileNameParsedArray);

            $directoryPath = 'torrents';

            if (!Storage::disk('local')->exists($directoryPath)) {
                Storage::disk('local')->makeDirectory($directoryPath);
            }

            Storage::disk('local')->copy($tempFile, $directoryPath.'/'.$torrentData['torrentName']);

            $createdInfo = OhysTorrent::query()->updateOrCreate(['uniqueID' => $torrentData['uniqueID']], $torrentData);

            $createdInfo->touch();

            $this->info('[Debug] Done: ' . $torrent['title'] . '.torrent');
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
        $fileBaseName = str_replace(['AACx2'], ['AAC'], $file);
        preg_match('/(?:\[([^\r\n]*)\][\W]?)?(?:(?:([^\r\n]+?)(?: - ([0-9.]+?(?: END)?|SP))?)[\W]?[(|[]([^\r\n(]+)? (\d+x\d+|\d{3,}[^\r\n ]*)? ([^\r\n]+)?[)\]][^.\r\n]*(?:\.([^\r\n.]*)(?:\.[\w]+)?)?)$/', $fileBaseName, $fileNameParsedArray);

        return $fileNameParsedArray;
    }

    private function extractTorrentData($torrent, $file, $fileNameParsedArray): array
    {
        $torrent = new Torrent($torrent);

        $itemID = Str::random(8);
        $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9]/', '', $fileNameParsedArray[3]);
        $episode = empty($episode) ? null : $episode;

        $metadataCodecParsed = empty($fileNameParsedArray[6]) ? [] : explode(' ', $fileNameParsedArray[6]);

        if ($metadataCodecParsed[0] == '2x') {
            array_shift($metadataCodecParsed);
        }

        if ($metadataCodecParsed[0] == '264') {
            $metadataCodecParsed[0] = 'x264';
        }

        $searchArray = NotifyAnime::searchByTitle($fileNameParsedArray[2], 1);

        if (!empty($searchArray)) {
            $animeUniqueID = $searchArray[0]->obj['uniqueID'];
            OhysRelation::query()->updateOrCreate([
                'uniqueID' => $itemID,
            ], [
                'matchingID' => $animeUniqueID,
            ]);
        }

        return [
            'uniqueID'                  => $itemID,
            'releaseGroup'              => 'Ohys-Raws',
            'broadcaster'               => $fileNameParsedArray[4] ?? null,
            'title'                     => $fileNameParsedArray[2],
            'episode'                   => $episode,
            'torrentName'               => $file . '.torrent',
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
