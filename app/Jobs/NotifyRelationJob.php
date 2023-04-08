<?php

namespace App\Jobs;

use App\Models\NotifyRelation;
use GuzzleHttp\Client;

class NotifyRelationJob extends Job
{
    protected array $notifyRelationItem;

    public function __construct(array $notifyRelationItem)
    {
        $this->notifyRelationItem = $notifyRelationItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->notifyRelationItem)) {
            return;
        }

        $client = $this->createHttpClient();
        $downloadedData = $this->fetchData($client, 'https://notify.moe/api/animerelations/'.$this->notifyRelationItem['notifyID']);

        if (!$downloadedData) {
            return;
        }

        if (empty($downloadedData['items'])) {
            return;
        }

        $notifyRelation = NotifyRelation::query()->where('notifyID', $this->notifyRelationItem['notifyID'])->first();

        if ($notifyRelation) {
            $this->assignRelationData($notifyRelation, $downloadedData);
            $notifyRelation->touch();
            $notifyRelation->save();
        } else {
            $newNotifyRelation = new NotifyRelation([
                'uniqueID' => $this->notifyRelationItem['uniqueID'],
                'notifyID' => $downloadedData['animeId'],
            ]);
            $this->assignRelationData($newNotifyRelation, $downloadedData);
            $newNotifyRelation->save();
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

    private function assignRelationData($notifyCharacterRelation, array $downloadedData)
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
            $notifyCharacterRelation->$key = $value;
        }
    }
}
