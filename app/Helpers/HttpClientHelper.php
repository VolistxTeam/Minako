<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class HttpClientHelper
{
    public Client $client;

    public function __construct()
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
        ];

        $this->client = new Client(['http_errors' => false, 'timeout' => 60.0, 'headers' => $headers]);
    }

    public function Get($url): ?string
    {
        $response = $this->client->get($url);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->getBody()->getContents();
    }
}
