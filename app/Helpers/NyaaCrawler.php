<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class NyaaCrawler
{
    private $client;

    public function __construct()
    {
        $this->client = $this->createHttpClient();
    }

    public function getAllTorrents(): array
    {
        $allTorrents = [];
        $page = 1;
        $isEmpty = false;
        $maxPages = 15; // Set a limit to prevent endless requests
        $previousTorrents = null; // To store data from the previous page

        while (! $isEmpty && $page < $maxPages) {
            sleep(1);
            $torrents = $this->getTorrents($page);

            if (empty($torrents)) {
                $isEmpty = true;
            } else {
                // Check if the current page torrents are the same as the previous page torrents
                if ($torrents === $previousTorrents) {
                    break; // If they are the same, break the loop
                }

                $allTorrents = array_merge($allTorrents, $torrents);
                $previousTorrents = $torrents; // Update the previous torrents
                $page++;
            }
        }

        ray(count($allTorrents));

        return $allTorrents;
    }

    public function getTorrents($page = 1): array
    {
        $response = $this->client->get('/?f=0&c=0_0&q='.urlencode('[Ohys-Raws]').'&p='.$page);

        $crawler = new Crawler($response->getBody()->getContents());

        return $crawler->filter('div.table-responsive table tbody tr.default')->each(function (Crawler $node) {
            return [
                'title' => $node->filter('td[colspan="2"] a')->last()->text(),
                'link' => 'https://nyaa.si'.$node->filter('td.text-center a[href^="/download/"]')->attr('href'),
                'size' => $node->filter('td.text-center')->eq(1)->text(),
                'date' => $node->filter('td.text-center')->eq(2)->text(),
                'downloads' => [
                    'url' => 'https://nyaa.si'.$node->filter('td.text-center a[href^="/download/"]')->attr('href'),
                    'magnet' => $node->filter('td.text-center a[href^="magnet:"]')->attr('href'),
                ],
            ];
        });
    }

    private function createHttpClient(): Client
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
        ];

        return new Client(['http_errors' => false, 'timeout' => 60.0, 'headers' => $headers, 'base_uri' => 'https://nyaa.si/']);
    }
}
