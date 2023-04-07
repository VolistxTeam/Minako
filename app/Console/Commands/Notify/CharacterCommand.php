<?php

namespace App\Console\Commands\Notify;

use App\Jobs\NotifyCharacterJob;
use App\Models\NotifyCharacter;
use Carbon\Carbon;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CharacterCommand extends Command
{
    protected $signature = 'minako:notify:characters';

    protected $description = 'Retrieve all character information from notify.moe.';

    private string $sitemapURL = 'https://notify.moe/sitemap/character.txt';
    private string $apiBaseURL = 'https://notify.moe/api/character/';
    private string $notifyBaseURL = 'https://notify.moe/character/';

    public function handle()
    {
        $this->setUnlimitedTimeLimit();
        $this->info('[!] Downloading ID lists from the server...');
        $headers = $this->getHeaders();
        $client = new Client(['http_errors' => false, 'timeout' => 60.0]);

        $notifyIDs = $this->getNotifyIDs($client, $headers);

        if (!$notifyIDs) {
            return;
        }

        $dbItems = NotifyCharacter::query()->whereDate('updated_at', '>', Carbon::now()->subDays(7))->select('id', 'notifyID', 'uniqueID', 'created_at', 'updated_at')->get();
        $dbItemIds = $dbItems->pluck('notifyID')->toArray();
        $cleanedArray = $this->compareAndRemove($dbItemIds, $notifyIDs);

        $this->processNotifyIDs($cleanedArray);
    }

    private function setUnlimitedTimeLimit()
    {
        $this->info('[Debug] Setting Time Limit To 0 (Unlimited)');
        set_time_limit(0);
    }

    private function getHeaders()
    {
        $faker = Factory::create();

        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => $faker->chrome,
        ];
    }

    private function getNotifyIDs($client, $headers)
    {
        $sitemapResponse = $client->get($this->sitemapURL, ['headers' => $headers]);

        if ($sitemapResponse->getStatusCode() != 200) {
            $this->error('[-] The sitemap does not found.');
            return false;
        }

        $rawNotifyIDs = explode("\n", (string)$sitemapResponse->getBody());
        return array_map(function ($item) {
            return str_replace($this->notifyBaseURL, '', $item);
        }, $rawNotifyIDs);
    }

    private function compareAndRemove(array $array1, array $array2)
    {
        foreach ($array1 as $key1 => $value1) {
            if (($key2 = array_search($value1, $array2)) !== false) {
                unset($array1[$key1]);
                unset($array2[$key2]);
            }
        }

        $result = array_merge($array1, $array2);

        return array_filter($result, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function processNotifyIDs($notifyIDs)
    {
        $totalCount = count($notifyIDs);

        if ($totalCount == 0) {
            $this->info('[!] No new character found.');
            return;
        }

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        $this->info(PHP_EOL . '[!] Querying for Work...' . PHP_EOL);

        foreach ($notifyIDs as $item) {
            dispatch(new NotifyCharacterJob($item));
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
