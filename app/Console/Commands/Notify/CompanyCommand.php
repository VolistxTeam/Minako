<?php

namespace App\Console\Commands\Notify;

use App\Models\NotifyAnime;
use App\Models\NotifyCompany;
use Carbon\Carbon;
use Exception;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CompanyCommand extends Command
{
    protected $signature = "minako:notify:company";

    protected $description = "Retrieve all company information from notify.moe.";

    public function handle()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');

        set_time_limit(0);

        $apiBaseURL = "https://notify.moe/api/company/";

        $faker = Factory::create();

        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => $faker->chrome,
        ];

        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        $allAnime = NotifyAnime::query()->select('id', 'notifyID', 'uniqueID', 'studios', 'producers', 'licensors')->get()->toArray();

        $this->info('[!] Starting the process to crawl information...');

        $totalCount = count($allAnime);
        $remainingCount = 1;

        foreach ($allAnime as $item) {
            try {
                if (!empty($item['studios'])) {
                    foreach ($item['studios'] as $studioItem) {
                        $dbStudioItem = NotifyCompany::query()->where('notifyID', $studioItem)->select('id', 'created_at', 'updated_at')->first();

                        $allowCrawlStudio = false;

                        if (!empty($dbStudioItem)) {
                            if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbStudioItem->updated_at))) {
                                $allowCrawlStudio = true;
                            }
                        } else {
                            $allowCrawlStudio = true;
                        }

                        if (!$allowCrawlStudio) {
                            continue;
                        }

                        $realAPIURL = $apiBaseURL . $studioItem;

                        $companyResponse = $client->get($realAPIURL, ['headers' => $headers]);

                        if ($companyResponse->getStatusCode() != 200) {
                            continue;
                        }

                        $downloadedData = (string)$companyResponse->getBody();

                        $downloadedData = json_decode($downloadedData, true);
                        $uniqueIDGen = substr(sha1('6ASRjSGuS5' . $studioItem . '5fqX2x73DMD84G2PtnC5'), 0, 8);

                        $notifyDBItem = NotifyCompany::query()->updateOrCreate([
                            'uniqueID' => $uniqueIDGen,
                            'notifyID' => $downloadedData['id'],
                        ], [
                            'name_english' => !empty($downloadedData['name']['english']) ? $downloadedData['name']['english'] : null,
                            'name_japanese' => !empty($downloadedData['name']['japanese']) ? $downloadedData['name']['japanese'] : null,
                            'name_synonyms' => !empty($downloadedData['name']['synonyms']) ? $downloadedData['name']['synonyms'] : null,
                            'description' => !empty($downloadedData['description']) ? $downloadedData['description'] : null,
                            'email' => !empty($downloadedData['email']) ? $downloadedData['email'] : null,
                            'links' => !empty($downloadedData['links']) ? $downloadedData['links'] : null,
                            'mappings' => !empty($downloadedData['mappings']) ? $downloadedData['mappings'] : null,
                            'location' => !empty($downloadedData['location']) ? $downloadedData['location'] : null
                        ]);

                        $notifyDBItem->touch();

                        $this->line('[-] Studio Item Saved: ' . $studioItem);
                    }
                }

                if (!empty($item['producers'])) {
                    foreach ($item['producers'] as $producerItem) {
                        $dbProducerItem = NotifyCompany::query()->where('notifyID', $producerItem)->select('id', 'created_at', 'updated_at')->first();

                        $allowCrawlProducer = false;

                        if (!empty($dbProducerItem)) {
                            if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbProducerItem['updated_at']))) {
                                $allowCrawlProducer = true;
                            }
                        } else {
                            $allowCrawlProducer = true;
                        }

                        if (!$allowCrawlProducer) {
                            continue;
                        }

                        $realAPIURL = $apiBaseURL . $producerItem;

                        $companyResponse = $client->get($realAPIURL, ['headers' => $headers]);

                        if ($companyResponse->getStatusCode() != 200) {
                            continue;
                        }

                        $downloadedData = (string)$companyResponse->getBody();

                        $downloadedData = json_decode($downloadedData, true);
                        $uniqueIDGen = substr(sha1('6ASRjSGuS5' . $producerItem . '5fqX2x73DMD84G2PtnC5'), 0, 8);

                        $notifyDBItem = NotifyCompany::query()->updateOrCreate([
                            'uniqueID' => $uniqueIDGen,
                            'notifyID' => $downloadedData['id'],
                        ], [
                            'name_english' => !empty($downloadedData['name']['english']) ? $downloadedData['name']['english'] : null,
                            'name_japanese' => !empty($downloadedData['name']['japanese']) ? $downloadedData['name']['japanese'] : null,
                            'name_synonyms' => !empty($downloadedData['name']['synonyms']) ? $downloadedData['name']['synonyms'] : null,
                            'description' => !empty($downloadedData['description']) ? $downloadedData['description'] : null,
                            'email' => !empty($downloadedData['email']) ? $downloadedData['email'] : null,
                            'links' => !empty($downloadedData['links']) ? $downloadedData['links'] : null,
                            'mappings' => !empty($downloadedData['mappings']) ? $downloadedData['mappings'] : null,
                            'location' => !empty($downloadedData['location']) ? $downloadedData['location'] : null
                        ]);

                        $notifyDBItem->touch();

                        $this->line('[-] Producer Item Saved: ' . $producerItem);
                    }
                }

                if (!empty($item['licensors'])) {
                    foreach ($item['licensors'] as $licensorItem) {
                        $dbLicensorItem = NotifyCompany::query()->where('notifyID', $licensorItem)->select('id', 'created_at', 'updated_at')->first();

                        $allowCrawlLicensor = false;

                        if (!empty($dbLicensorItem)) {
                            if (Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbLicensorItem['updated_at']))) {
                                $allowCrawlLicensor = true;
                            }
                        } else {
                            $allowCrawlLicensor = true;
                        }

                        if (!$allowCrawlLicensor) {
                            continue;
                        }

                        $realAPIURL = $apiBaseURL . $licensorItem;

                        $companyResponse = $client->get($realAPIURL, ['headers' => $headers]);

                        if ($companyResponse->getStatusCode() != 200) {
                            continue;
                        }

                        $downloadedData = (string)$companyResponse->getBody();

                        $downloadedData = json_decode($downloadedData, true);
                        $uniqueIDGen = substr(sha1('6ASRjSGuS5' . $licensorItem . '5fqX2x73DMD84G2PtnC5'), 0, 8);

                        $notifyDBItem = NotifyCompany::query()->updateOrCreate([
                            'uniqueID' => $uniqueIDGen,
                            'notifyID' => $downloadedData['id'],
                        ], [
                            'name_english' => !empty($downloadedData['name']['english']) ? $downloadedData['name']['english'] : null,
                            'name_japanese' => !empty($downloadedData['name']['japanese']) ? $downloadedData['name']['japanese'] : null,
                            'name_synonyms' => !empty($downloadedData['name']['synonyms']) ? $downloadedData['name']['synonyms'] : null,
                            'description' => !empty($downloadedData['description']) ? $downloadedData['description'] : null,
                            'email' => !empty($downloadedData['email']) ? $downloadedData['email'] : null,
                            'links' => !empty($downloadedData['links']) ? $downloadedData['links'] : null,
                            'mappings' => !empty($downloadedData['mappings']) ? $downloadedData['mappings'] : null,
                            'location' => !empty($downloadedData['location']) ? $downloadedData['location'] : null
                        ]);

                        $notifyDBItem->touch();

                        $this->line('[-] Licensor Item Saved: ' . $licensorItem);
                    }
                }

                $this->info('[+] Item Saved [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
            } catch (Exception $ex) {
                $this->error('[-] Skipping item. Reason: Unknown Error [' . $remainingCount . '/' . $totalCount . ']');
                $remainingCount++;
                continue;

            }
        }
    }
}
