<?php

namespace App\Helpers;

use App\Facades\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class JikanAPIHelper
{
    public function getAnimeEpisodes($malID)
    {
        $allData = [];
        $page = 1;
        $attempts = 0;
        $hasNextPage = false;

        do {
            try {
                $response = HttpClient::Get("anime/{$malID}/episodes?page={$page}");
                $data = json_decode($response, true);

                if (!empty($data['data'])) {
                    $allData = array_merge($allData, $data['data']);
                    $hasNextPage = $data['pagination']['has_next_page'] ?? false;
                    $page++;
                    $attempts = 0; // Reset attempts after a successful request
                } else {
                    $hasNextPage = false;
                }
            } catch (GuzzleException $e) {
                if ($e->getCode() == 429) { // HTTP 429 Too Many Requests
                    $attempts++;
                    if ($attempts < 5) { // Allow a few retries
                        sleep(10); // Wait for 10 seconds before retrying

                        continue; // Retry the loop
                    }

                    return null; // Stop retrying after several attempts
                }

                return null; // Other Guzzle exceptions result in termination of the loop
            }
        } while ($hasNextPage);

        return $allData;
    }
}
