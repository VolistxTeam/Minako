<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyCompany;
use Carbon\Carbon;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\progress;

class CompanyCommand extends Command
{
    protected $signature = 'minako:notify:company';

    protected $description = 'Retrieve all company information from notify.moe.';

    private string $dataSource = 'https://notify.moe/api/types/Company/download';
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

        $tempFilePath = tempnam(sys_get_temp_dir(), 'CompanyData');
        file_put_contents($tempFilePath, $response->getBody()->getContents());
        return $tempFilePath;
    }

    private function getRecentDBItems(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        return NotifyCompany::query()
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
        $progress = progress(label: 'Saving Company Data', steps: $fileSize);
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
        $notifyCompany = NotifyCompany::query()->where('notifyID', $animeData['id'])->first();

        if ($notifyCompany) {
            $this->assignCompanyData($notifyCompany, $animeData);
            $notifyCompany->touch();
            $notifyCompany->save();
        } else {
            $newNotifyCompany = new NotifyCompany([
                'uniqueID' => $uniqueId,
                'notifyID' => $animeData['id'],
            ]);
            $this->assignCompanyData($newNotifyCompany, $animeData);
            $newNotifyCompany->save();
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

    private function assignCompanyData($notifyCompany, array $downloadedData): void
    {
        $keys = [
            'name_english'  => ['name', 'english'],
            'name_japanese' => ['name', 'japanese'],
            'name_synonyms' => ['name', 'synonyms'],
            'description'   => ['description'],
            'email'         => ['email'],
            'links'         => ['links'],
            'mappings'      => ['mappings'],
            'location'      => ['location'],
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyCompany->$key = $value;
        }
    }
}

