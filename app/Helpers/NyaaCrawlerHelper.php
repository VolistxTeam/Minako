<?php

namespace App\Helpers;

use App\Facades\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class NyaaCrawlerHelper
{
    public function getAllTorrents(): array
    {
        $allTorrents = [];
        $page = 1;
        $isEmpty = false;
        $maxPages = 15; // Set a limit to prevent endless requests
        $previousTorrents = null; // To store data from the previous page

        while (!$isEmpty && $page < $maxPages) {
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

        return $allTorrents;
    }

    private function getTorrents($page = 1): array
    {
        $response = HttpClient::Get('https://nyaa.si/?f=0&c=0_0&q=' . urlencode('[Ohys-Raws]') . '&p=' . $page);

        $crawler = new Crawler($response);

        return $crawler->filter('div.table-responsive table tbody tr.default')->each(function (Crawler $node) {
            return [
                'title' => $node->filter('td[colspan="2"] a')->last()->text(),
                'link' => 'https://nyaa.si' . $node->filter('td.text-center a[href^="/download/"]')->attr('href'),
                'size' => $node->filter('td.text-center')->eq(1)->text(),
                'date' => $node->filter('td.text-center')->eq(2)->text(),
                'downloads' => [
                    'url' => 'https://nyaa.si' . $node->filter('td.text-center a[href^="/download/"]')->attr('href'),
                    'magnet' => $node->filter('td.text-center a[href^="magnet:"]')->attr('href'),
                ],
            ];
        });
    }
}
