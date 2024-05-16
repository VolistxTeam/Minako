<?php

namespace App\Console\Commands\Notify;

use App\Facades\HttpClient;
use App\Models\NotifyAnime;
use App\Models\NotifyAnimeCharacter;
use App\Repositories\AnimeRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use function Laravel\Prompts\progress;

class AnimeCharacterCommand extends Command
{
    private AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        parent::__construct();
        $this->animeRepository = $animeRepository;
    }

    protected $signature = 'minako:notify:anime-character';

    protected $description = 'Retrieve all anime character information from notify.moe.';
    private string $dataSource = 'https://notify.moe/api/types/AnimeCharacters/download';

    public function handle()
    {
        set_time_limit(0);

        $response = HttpClient::Get($this->dataSource);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'AnimeCharacterData');

        file_put_contents($tempFilePath, $response);

        $recentCharacters = NotifyAnimeCharacter::query()
            ->whereDate('updated_at', '>', Carbon::now()->subDays(7))
            ->pluck('notifyID')
            ->toArray();

        $this->parseAndProcessData($tempFilePath, $recentCharacters);

        unlink($tempFilePath);
    }

    private function parseAndProcessData(string $filePath, array $recentCharacters)
    {
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->components->error("Cant find temp file for downloaded characters data.");
        }

        $animes = NotifyAnime::query()
            ->select('notifyID', 'uniqueID')
            ->get()->toArray();

        // Estimate the number of lines - this could be improved with a more accurate measure
        $fileSize = filesize($filePath);
        $progress = progress(label: 'Saving Anime Character Data', steps: $fileSize);

        $progress->start();

        $processedBytes = 0;

        while (!feof($handle)) {
            $linePosition = ftell($handle);
            $line = fgets($handle);
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) {
                continue;
            }

            if (preg_match('/^[a-zA-Z0-9]+$/', $trimmedLine)) {
                $currentId = $trimmedLine;
                $line = fgets($handle); // Assume the next line is the JSON data
                $animeData = json_decode(trim($line), true);

                $dbAnime = array_filter($animes, function ($anime) use ($currentId) {
                    return $anime['notifyID'] == $currentId;
                });

                if (count($dbAnime)) {
                    if (!in_array($currentId, $recentCharacters)) {
                        $this->animeRepository->createOrUpdateNotifyCharacter(reset($dbAnime)['uniqueID'], $animeData);
                    }
                }
            }

            $processedBytes = ftell($handle) - $linePosition;
            $progress->advance($processedBytes);
        }

        $progress->finish();
        fclose($handle);
    }
}
