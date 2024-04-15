<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

class NotifyPosterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $id;

    protected string $notifyID;

    protected string $uniqueID;

    protected string $imageExtension;

    public function __construct(int $id, string $notifyID, string $uniqueID, string $imageExtension)
    {
        $this->id = $id;
        $this->notifyID = $notifyID;
        $this->uniqueID = $uniqueID;
        $this->imageExtension = $imageExtension;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = $this->getClient();
        $imageUrls = $this->getImageUrls();

        $imageData = $this->fetchImage($client, $imageUrls['large']);

        if (! $imageData) {
            $imageData = $this->fetchImage($client, $imageUrls['original']);
            if (! $imageData) {
                return;
            }
        }

        $this->storeImage($imageData);
    }

    private function getClient()
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Keep-Alive' => '300',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
        ];

        return new Client(['http_errors' => false, 'timeout' => 60.0, 'headers' => $headers]);
    }

    private function getImageUrls(): array
    {
        return [
            'original' => 'https://media.notify.moe/images/anime/original/'.$this->notifyID.$this->imageExtension,
            'large' => 'https://media.notify.moe/images/anime/large/'.$this->notifyID.$this->imageExtension,
        ];
    }

    private function fetchImage($client, $imageUrl)
    {
        $response = $client->request('GET', $imageUrl);
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->getBody()->getContents();
    }

    private function storeImage($imageData): void
    {
        $image = Image::read($imageData)->encode(new JpegEncoder(quality: 100));
        Storage::disk('local')->put('posters/'.$this->uniqueID.'.jpg', $image);
    }
}
