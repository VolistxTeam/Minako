<?php

namespace App\Jobs;

use App\Models\NotifyCharacterRelation;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class NotifyCharacterRelationJob extends Job
{
    protected string $notifyCharacterRelationItem;

    public function __construct(string $notifyCharacterRelationItem)
    {
        $this->notifyCharacterRelationItem = $notifyCharacterRelationItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->notifyCharacterRelationItem)) {
            return;
        }

        $client = $this->createHttpClient();
        $downloadedData = $this->fetchData($client, 'https://notify.moe/api/animecharacters/'.$this->notifyCharacterRelationItem);

        if (!$downloadedData) {
            return;
        }

        $uniqueId = Str::random(10);

        if (empty($downloadedData['items'])) {
            return;
        }

        $notifyCharacterRelation = NotifyCharacterRelation::query()->where('notifyID', $this->notifyCharacterRelationItem)->first();

        if ($notifyCharacterRelation) {
            $this->assignCharacterRelationData($notifyCharacterRelation, $downloadedData);
            $notifyCharacterRelation->touch();
            $notifyCharacterRelation->save();
        } else {
            $newNotifyCharacterRelation = new NotifyCharacterRelation([
                'uniqueID' => $uniqueId,
                'notifyID' => $this->notifyCharacterRelationItem,
            ]);
            $this->assignCharacterRelationData($newNotifyCharacterRelation, $downloadedData);
            $newNotifyCharacterRelation->save();
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

        return $this->isJson($data) ? json_decode($data, true) : null;
    }

    private function isJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }

    private function assignCharacterRelationData($notifyCharacterRelation, array $downloadedData)
    {
        $keys = [
            'items' => ['items']
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyCharacterRelation->$key = $value;
        }
    }
}
