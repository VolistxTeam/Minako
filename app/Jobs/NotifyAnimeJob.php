<?php

namespace App\Jobs;

use App\Models\NotifyAnime;
use GuzzleHttp\Client;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NotifyAnimeJob extends Job
{
    use Batchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $notifyItem;

    public function __construct(string $notifyItem)
    {
        $this->notifyItem = $notifyItem;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->notifyItem)) {
            return;
        }

        $client = $this->createHttpClient();
        $downloadedData = $this->fetchData($client, 'https://notify.moe/api/anime/'.$this->notifyItem);

        if (!$downloadedData) {
            return;
        }

        $uniqueId = Str::random(10);
        $notifyAnime = NotifyAnime::query()->where('notifyID', $this->notifyItem)->first();

        if ($notifyAnime) {
            $this->assignAnimeData($notifyAnime, $downloadedData);
            $notifyAnime->save();
        } else {
            $newNotifyAnime = new NotifyAnime([
                'uniqueID' => $uniqueId,
                'notifyID' => $this->notifyItem,
            ]);
            $this->assignAnimeData($newNotifyAnime, $downloadedData);
            $newNotifyAnime->save();
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

    private function assignAnimeData($notifyAnime, array $downloadedData)
    {
        $keys = [
            'type'              => ['type'],
            'title_canonical'   => ['title', 'canonical'],
            'title_romaji'      => ['title', 'romaji'],
            'title_english'     => ['title', 'english'],
            'title_japanese'    => ['title', 'japanese'],
            'title_hiragana'    => ['title', 'hiragana'],
            'title_synonyms'    => ['title', 'synonyms'],
            'summary'           => ['summary'],
            'status'            => ['status'],
            'genres'            => ['genres'],
            'startDate'         => ['startDate'],
            'endDate'           => ['endDate'],
            'episodeCount'      => ['episodeCount'],
            'episodeLength'     => ['episodeLength'],
            'source'            => ['source'],
            'image_extension'   => ['image', 'extension'],
            'image_width'       => ['image', 'width'],
            'image_height'      => ['image', 'height'],
            'firstChannel'      => ['firstChannel'],
            'rating_overall'    => ['rating', 'overall'],
            'rating_story'      => ['rating', 'story'],
            'rating_visuals'    => ['rating', 'visuals'],
            'rating_soundtrack' => ['rating', 'soundtrack'],
            'trailers'          => ['trailers'],
            'n_episodes'        => ['episodes'],
            'mappings'          => ['mappings'],
            'studios'           => ['studios'],
            'producers'         => ['producers'],
            'licensors'         => ['licensors'],
        ];

        foreach ($keys as $key => $path) {
            $value = $downloadedData;
            foreach ($path as $p) {
                $value = $value[$p] ?? null;
                if ($value === null) {
                    break;
                }
            }
            $notifyAnime->$key = $value;
        }
    }
}
