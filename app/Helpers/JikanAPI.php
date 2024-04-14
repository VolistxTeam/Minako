<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class JikanAPI
{
    private $client;

    public function __construct() {
        $this->client = $this->createHttpClient();
    }

    public function getAnimeEpisodes($malID) {
        $allData = [];
        $page = 1;
        do {
            try {
                $response = $this->client->get("anime/{$malID}/episodes?page={$page}");
                $data = json_decode($response->getBody()->getContents(), true);

                if (!empty($data['data'])) {
                    $allData = array_merge($allData, $data['data']);
                    $hasNextPage = $data['pagination']['has_next_page'] ?? false;
                    $page++;
                } else {
                    $hasNextPage = false;
                }
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                return null;
            }
        } while ($hasNextPage);

        return $allData;
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

        return new Client(['http_errors' => false, 'timeout' => 60.0, 'headers' => $headers, 'base_uri' => 'https://api.jikan.moe/v4/']);
    }
}
