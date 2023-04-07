<?php

namespace App\Jobs;

use App\Models\NotifyCompany;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class NotifyCompanyJob extends Job
{
    protected $item;
    protected Client $client;
    protected string $apiBaseUrl = 'https://notify.moe/api/company/';
    protected array $companyDataCache = [];

    public function __construct($item)
    {
        $this->item = $item;
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

    public function handle()
    {
        try {
            $this->processCompanies($this->item['studios']);
            $this->processCompanies($this->item['producers']);
            $this->processCompanies($this->item['licensors']);

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    private function processCompanies($companies)
    {
        if (empty($companies)) {
            return;
        }

        foreach ($companies as $company) {
            $dbCompany = NotifyCompany::query()->where('notifyID', $company)->select('id', 'created_at', 'updated_at')->first();

            if (!$this->shouldCrawl($dbCompany)) {
                continue;
            }

            $companyData = $this->fetchCompanyData($company);

            if (!$companyData) {
                continue;
            }

            $this->saveCompanyData($companyData);
        }
    }

    private function shouldCrawl($dbCompany)
    {
        if (!$dbCompany) {
            return true;
        }

        return Carbon::now()->subDays(7)->greaterThan(Carbon::createFromTimeString($dbCompany->updated_at));
    }

    private function fetchCompanyData($companyId)
    {
        if (isset($this->companyDataCache[$companyId])) {
            return $this->companyDataCache[$companyId];
        }

        $url = $this->apiBaseUrl . $companyId;
        $client = $this->createHttpClient();
        $response = $client->get($url);

        if ($response->getStatusCode() != 200) {
            return null;
        }

        $data = json_decode((string)$response->getBody(), true);
        $this->companyDataCache[$companyId] = $data;

        return $data;
    }

    private function saveCompanyData($data)
    {
        $uniqueId = Str::random(10);
        $notifyCompany = NotifyCompany::query()->where('notifyID', $data['id'])->first();

        if ($notifyCompany) {
            $this->assignCompanyData($notifyCompany, $data);
            $notifyCompany->touch();
            $notifyCompany->save();
        } else {
            $newNotifyCompany = new NotifyCompany([
                'uniqueID' => $uniqueId,
                'notifyID' => $data['id'],
            ]);
            $this->assignCompanyData($newNotifyCompany, $data);
            $newNotifyCompany->save();
        }
    }

    private function assignCompanyData($notifyCompany, array $downloadedData)
    {
        $keys = [
            'name_english' => ['name', 'english'],
            'name_japanese' => ['name', 'japanese'],
            'name_synonyms' => ['name', 'synonyms'],
            'description' => ['description'],
            'email' => ['email'],
            'links' => ['links'],
            'mappings' => ['mappings'],
            'location' => ['location'],
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