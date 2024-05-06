<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use Carbon\Carbon;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\progress;

class AnimeCommand extends Command
{
    protected $signature = 'minako:notify:anime';

    protected $description = 'Retrieve all anime information from notify.moe.';

    private string $dataSource = 'https://notify.moe/api/types/Anime/download';

    private string $tempFilePath;

    public function handle()
    {
        set_time_limit(0);

        $headers = $this->getHeaders();
        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);
        $this->tempFilePath = $this->downloadData($client, $headers);
        if (!$this->tempFilePath) {
            $this->components->error('Failed to download data.');

            return;
        }

        $dbItems = $this->getRecentDBItems();

        $this->parseAndProcessData($this->tempFilePath, $dbItems);

        unlink($this->tempFilePath);
    }

    private function getHeaders(): array
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

    private function downloadData(Client $client, array $headers): ?string
    {
        $response = $client->get($this->dataSource, ['headers' => $headers]);
        if ($response->getStatusCode() != 200) {
            return null;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'AnimeData');
        file_put_contents($tempFilePath, $response->getBody()->getContents());

        return $tempFilePath;
    }

    private function getRecentDBItems(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        return NotifyAnime::query()
            ->whereDate('updated_at', '>', $sevenDaysAgo)
            ->pluck('notifyID')
            ->toArray();
    }

    private function parseAndProcessData(string $filePath, array $dbItems)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        // Estimate the number of lines - this could be improved with a more accurate measure
        $fileSize = filesize($filePath);
        $progress = progress(label: 'Saving Anime Data', steps: $fileSize);
        $progress->start();

        $processedBytes = 0;
        while (!feof($handle)) {
            $linePosition = ftell($handle);
            $line = fgets($handle);
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) {
                continue;
            }

            if (preg_match('/^[a-zA-Z0-9]+$/', $trimmedLine)) {
                $currentId = $trimmedLine;
                $line = fgets($handle); // Assume the next line is the JSON data
                $animeData = json_decode(trim($line), true);
                if (!in_array($currentId, $dbItems)) {
                    $this->processData($animeData);
                }
            }
            $processedBytes = ftell($handle) - $linePosition;
            $progress->advance($processedBytes);
        }

        $progress->finish();
        fclose($handle);
    }

    private function processData(array $animeData)
    {
        $uniqueId = Str::random(8);
        $notifyAnime = NotifyAnime::query()->where('notifyID', $animeData['id'])->first();

        if ($notifyAnime) {
            $this->assignAnimeData($notifyAnime, $animeData);
            $notifyAnime->touch();
            $notifyAnime->save();
        } else {
            $newNotifyAnime = new NotifyAnime([
                'uniqueID' => $uniqueId,
                'notifyID' => $animeData['id'],
            ]);
            $this->assignAnimeData($newNotifyAnime, $animeData);
            $newNotifyAnime->save();
        }
    }

    private function assignAnimeData($notifyAnime, array $downloadedData): void
    {
        $keys = [
            'type' => ['type'],
            'title_canonical' => ['title', 'canonical'],
            'title_romaji' => ['title', 'romaji'],
            'title_english' => ['title', 'english'],
            'title_japanese' => ['title', 'japanese'],
            'title_hiragana' => ['title', 'hiragana'],
            'title_synonyms' => ['title', 'synonyms'],
            'summary' => ['summary'],
            'status' => ['status'],
            'genres' => ['genres'],
            'startDate' => ['startDate'],
            'endDate' => ['endDate'],
            'episodeCount' => ['episodeCount'],
            'episodeLength' => ['episodeLength'],
            'source' => ['source'],
            'image_extension' => ['image', 'extension'],
            'image_width' => ['image', 'width'],
            'image_height' => ['image', 'height'],
            'firstChannel' => ['firstChannel'],
            'rating_overall' => ['rating', 'overall'],
            'rating_story' => ['rating', 'story'],
            'rating_visuals' => ['rating', 'visuals'],
            'rating_soundtrack' => ['rating', 'soundtrack'],
            'trailers' => ['trailers'],
            'n_episodes' => ['episodes'],
            'mappings' => ['mappings'],
            'studios' => ['studios'],
            'producers' => ['producers'],
            'licensors' => ['licensors'],
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyAnime->$key = $value;
        }
    }
}
