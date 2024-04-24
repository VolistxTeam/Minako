<?php

namespace App\Console\Commands\Ohys;

use App\Classes\Torrent;
use App\Helpers\NyaaCrawler;
use App\Models\NotifyAnime;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use App\Repositories\AnimeRepository;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class DownloadCommand extends Command
{
    protected $signature = 'minako:ohys:download';

    protected $description = 'Check, download and parse the latest torrents using a special permission link.';

    protected AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        parent::__construct();
        $this->animeRepository = $animeRepository;
    }

    public function handle()
    {
        set_time_limit(0);

        $headers = $this->getHeaders();
        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $response = $client->get('https://nyaa.si', ['headers' => $headers]);

        if ($response->getStatusCode() != 200) {
            $this->error('[-] Error occurred while connecting to the nyaa.si server.');

            return 0;
        }

        $this->components->info('Downloading All Ohys-Raws Torrents...');
        $nyaaCrawler = new NyaaCrawler();
        $allTorrents = $nyaaCrawler->getAllTorrents();

        $this->components->info('Processing Torrents...');
        foreach ($allTorrents as $torrent) {
            if (OhysTorrent::query()->where('torrentName', $torrent['title'] . '.torrent')->first()) {
                break;
            }

            $tempFile = $temporaryDirectory->path(Str::random(15) . '.torrent');

            try {
                $response = $client->request('GET', $torrent['downloads']['url'], ['headers' => $headers, 'sink' => $tempFile]);
            } catch (GuzzleException $e) {
                $this->line('Download failed: ' . $e->getMessage() . ' Continue...');
                continue;
            }

            if (!file_exists($tempFile) || !is_readable($tempFile)) {
                $this->line('Temporary file not found or not readable. Continue...');
                continue;
            }

            $fileNameParsedArray = $this->parseFileName($torrent['title'] . '.torrent');
            if (count($fileNameParsedArray) === 0 || empty($fileNameParsedArray[2])) {
                $this->line('Filename parsing failed. Continue...');
                continue;
            }

            $torrentData = $this->extractTorrentData(file_get_contents($tempFile), $torrent['title'], $fileNameParsedArray);

            $directoryPath = 'torrents';
            if (!Storage::disk('local')->exists($directoryPath)) {
                Storage::disk('local')->makeDirectory($directoryPath);
            }

            $destinationPath = $directoryPath . '/' . $torrent['title'] . '.torrent';
            if (!Storage::disk('local')->put($destinationPath, file_get_contents($tempFile))) {
                $this->error("Failed to write file to '$destinationPath'. Check permissions and disk space.");
                continue;
            }

            $createdInfo = OhysTorrent::query()->updateOrCreate(['uniqueID' => $torrentData['uniqueID']], $torrentData);
            $createdInfo->touch();

            $this->info('[Debug] Done: ' . $torrent['title'] . '.torrent');
        }

        $temporaryDirectory->delete();

        return 0;
    }

//    public function handle()
//    {
//        set_time_limit(0);
//
//        $directoryPath = 'torrents'; // Specify your directory containing torrent files
//        $files = Storage::disk('local')->files($directoryPath); // Get all files in the directory
//
//        $this->components->info('Processing Torrents from local folder...');
//
//        foreach ($files as $file) {
//            $tempFile = storage_path('app/' . $file); // Adjust path according to your storage configuration
//
//            if (!file_exists($tempFile) || !is_readable($tempFile)) {
//                $this->line("Temporary file $tempFile not found or not readable. Continue...");
//                continue;
//            }
//
//            $fileName = basename($file); // Get the filename from path
//            $fileNameParsedArray = $this->parseFileName($fileName);
//
//            if (count($fileNameParsedArray) === 0 || empty($fileNameParsedArray[2])) {
//                $this->line('Filename parsing failed for ' . $fileName . '. Continue...');
//                continue;
//            }
//
//            $fileContents = file_get_contents($tempFile);
//            $torrentData = $this->extractTorrentData($fileContents, $fileNameParsedArray[0], $fileNameParsedArray);
//
//            // If uniqueID and other required data are handled in extractTorrentData
//            $createdInfo = OhysTorrent::query()->updateOrCreate(['uniqueID' => $torrentData['uniqueID']], $torrentData);
//            $createdInfo->touch();
//
//            $this->info('[Debug] Done: ' . $fileName);
//        }
//
//        return 0;
//    }


    private function getHeaders()
    {
        $faker = Factory::create();

        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => $faker->chrome(),
        ];
    }

    private function getDecodedOhysRepo($response)
    {
        $responseBody = (string)$response->getBody();

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

        $itemID = substr(sha1($torrent), 0, 8);
        ray($itemID);
        $episode = empty($fileNameParsedArray[3]) ? null : preg_replace('/[^0-9]/', '', $fileNameParsedArray[3]);
        $episode = empty($episode) ? null : $episode;

        $metadataCodecParsed = empty($fileNameParsedArray[6]) ? [] : explode(' ', $fileNameParsedArray[6]);

        if ($metadataCodecParsed[0] == '2x') {
            array_shift($metadataCodecParsed);
        }

        if ($metadataCodecParsed[0] == '264') {
            $metadataCodecParsed[0] = 'x264';
        }

        $searchArray = $this->animeRepository->searchNotifyAnimeByTitle($fileNameParsedArray[2], 1);

        if (!empty($searchArray)) {
            $animeUniqueID = $searchArray[0]->obj['uniqueID'];
            OhysRelation::query()->updateOrCreate([
                'uniqueID' => $itemID,
            ], [
                'matchingID' => $animeUniqueID,
            ]);
        }

        return [
            'uniqueID' => $itemID,
            'releaseGroup' => 'Ohys-Raws',
            'broadcaster' => $fileNameParsedArray[4] ?? null,
            'title' => $fileNameParsedArray[2],
            'episode' => $episode,
            'torrentName' => $file . '.torrent',
            'info_totalHash' => $torrent->hash_info(),
            'info_totalSize' => $torrent->size(2),
            'info_createdDate' => date('Y-m-d H:i:s', $torrent->creation_date()),
            'info_torrent_announces' => $torrent->announce(),
            'info_torrent_files' => $torrent->content(2),
            'metadata_video_resolution' => $fileNameParsedArray[5] ?? null,
            'metadata_video_codec' => $metadataCodecParsed[0] ?? null,
            'metadata_audio_codec' => $metadataCodecParsed[1] ?? null,
            'hidden_download_magnet' => $torrent->magnet(false),
        ];
    }
}
