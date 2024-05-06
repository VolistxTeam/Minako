<?php

namespace App\Jobs;

use App\Facades\HttpClient;
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
        $imageUrls = [
            'original' => 'https://media.notify.moe/images/anime/original/' . $this->notifyID . $this->imageExtension,
            'large' => 'https://media.notify.moe/images/anime/large/' . $this->notifyID . $this->imageExtension,
        ];

        $imageData = HttpClient::Get($imageUrls['large']);

        if (!$imageData) {
            $imageData = HttpClient::Get($imageUrls['original']);
            if (!$imageData) {
                return;
            }
        }

        $image = Image::read($imageData)->encode(new JpegEncoder(quality: 100));
        Storage::disk('local')->put('posters/' . $this->uniqueID . '.jpg', $image);
    }
}
