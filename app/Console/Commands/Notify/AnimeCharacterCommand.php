<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use App\Models\NotifyAnimeCharacter;
use Carbon\Carbon;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use function Laravel\Prompts\progress;

class AnimeCharacterCommand extends Command
{
    protected $signature = 'minako:notify:anime-character';
    protected $description = 'Retrieve all anime character information from notify.moe.';

    private string $dataSource = 'https://notify.moe/api/types/AnimeCharacters/download';
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

    private function downloadData(Client $client, array $headers): ?string
    {
        $response = $client->get($this->dataSource, ['headers' => $headers]);
        if ($response->getStatusCode() != 200) {
            return null;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'AnimeCharacterData');
        file_put_contents($tempFilePath, $response->getBody()->getContents());
        return $tempFilePath;
    }

    private function getRecentDBItems(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        return NotifyAnimeCharacter::query()
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

        $notifyAnime = NotifyAnime::query()
            ->pluck('notifyID')
            ->toArray();

        // Estimate the number of lines - this could be improved with a more accurate measure
        $fileSize = filesize($filePath);
        $progress = progress(label: 'Saving Anime Character Data', steps: $fileSize);
        $progress->start();

        $processedBytes = 0;
        while (!feof($handle)) {
            $linePosition = ftell($handle);
            $line = fgets($handle);
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) continue;

            if (preg_match('/^[a-zA-Z0-9]+$/', $trimmedLine)) {
                $currentId = $trimmedLine;
                $line = fgets($handle); // Assume the next line is the JSON data
                $animeData = json_decode(trim($line), true);

                // Check if the current ID is in the notifyAnime array, proceed if yes, skip if no
                if (in_array($currentId, $notifyAnime)) {
                    if (!in_array($currentId, $dbItems)) {
                        $this->processData($animeData);
                    }
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
        $notifyAnime = NotifyAnime::query()->where('notifyID', $animeData['animeId'])->select('uniqueID')->first();
        $notifyAnimeCharacter = NotifyAnimeCharacter::query()->where('notifyID', $animeData['animeId'])->first();

        if ($notifyAnimeCharacter) {
            $this->assignAnimeCharacterData($notifyAnimeCharacter, $animeData);
            $notifyAnimeCharacter->touch();
            $notifyAnimeCharacter->save();
        } else {
            if (!empty($notifyAnime->uniqueID)) {
                $newNotifyAnimeCharacter = new NotifyAnimeCharacter([
                    'uniqueID' => $notifyAnime->uniqueID,
                    'notifyID' => $animeData['animeId'],
                ]);

                $this->assignAnimeCharacterData($newNotifyAnimeCharacter, $animeData);
                $newNotifyAnimeCharacter->save();
            }
        }
    }

    private function getHeaders(): array
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

    private function assignAnimeCharacterData($notifyAnimeCharacter, array $downloadedData): void
    {
        $keys = [
            'items' => ['items'],
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyAnimeCharacter->$key = $value;
        }
    }
}

