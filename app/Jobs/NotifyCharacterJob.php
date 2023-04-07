<?php

namespace App\Jobs;

use App\Models\NotifyCharacter;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class NotifyCharacterJob extends Job
{
    protected string $notifyCharacterItem;

    public function __construct(string $notifyCharacterItem)
    {
        $this->notifyCharacterItem = $notifyCharacterItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = $this->createHttpClient();
        $apiBaseUrl = 'https://notify.moe/api/character/';

        if (empty($this->notifyCharacterItem)) {
            return;
        }

        $realApiUrl = $apiBaseUrl.$this->notifyCharacterItem;
        $downloadedData = $this->fetchData($client, $realApiUrl);

        if (!$downloadedData) {
            return;
        }

        $uniqueId = Str::random(10);
        $notifyCharacter = NotifyCharacter::query()->where('notifyID', $this->notifyCharacterItem)->first();

        if ($notifyCharacter) {
            $this->assignDownloadedData($notifyCharacter, $downloadedData);
            $notifyCharacter->touch();
            $notifyCharacter->save();
        } else {
            $newNotifyCharacter = new NotifyCharacter([
                'uniqueID' => $uniqueId,
                'notifyID' => $this->notifyCharacterItem,
            ]);
            $this->assignDownloadedData($newNotifyCharacter, $downloadedData);
            $newNotifyCharacter->save();
        }
    }

    private function createHttpClient(): Client
    {
        $headers = [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control'   => 'max-age=0',
            'Connection'      => 'keep-alive',
            'Keep-Alive'      => '300',
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
        ];

        return new Client(['http_errors' => false, 'timeout' => 60.0, 'headers' => $headers]);
    }

    private function fetchData(Client $client, string $url): ?array
    {
        $response = $client->get($url);

        if ($response->getStatusCode() != 200) {
            return null;
        }

        $data = (string) $response->getBody();

        if (!$this->isJson($data)) {
            return null;
        }

        return json_decode($data, true);
    }

    private function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }

    private function assignDownloadedData($notifyDbItem, array $downloadedData): void
    {
        $keys = [
            'name_canonical'  => ['name', 'canonical'],
            'name_english'    => ['name', 'english'],
            'name_japanese'   => ['name', 'japanese'],
            'name_synonyms'   => ['name', 'synonyms'],
            'image_extension' => ['image', 'extension'],
            'image_width'     => ['image', 'width'],
            'image_height'    => ['image', 'height'],
            'description'     => ['description'],
            'spoilers'        => ['spoilers'],
            'attributes'      => ['attributes'],
            'mappings'        => ['mappings'],
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyDbItem->$key = $value;
        }
    }
}
