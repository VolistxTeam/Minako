<?php

namespace App\Repositories;


use App\Facades\StringOperations;
use App\Models\MALAnime;
use App\Models\NotifyAnime;
use App\Models\NotifyAnimeCharacter;
use App\Models\NotifyCharacter;
use App\Models\NotifyCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AnimeRepository
{
    const MIN_STRING_SIMILARITY = 0.89;
    const CACHE_DURATION = 3600; // Cache results for 1 hour

    public function searchNotifyAnimeByTitle(string $originalTerm, int $maxLength, $type = null): array
    {
        return $this->searchModel($originalTerm, $maxLength, NotifyAnime::class, [
            'title_canonical', 'title_english', 'title_romaji', 'title_japanese', 'title_hiragana', 'title_synonyms'
        ]);
    }

    public function searchMALAnimeByName(string $originalTerm, int $maxLength, $type = null): array
    {
        return $this->searchModel($originalTerm, $maxLength, MALAnime::class, [
            'title', 'title_japanese', 'title_romanji'
        ]);
    }

    public function searchNotifyCharacterByName(string $originalTerm, int $maxLength, $type = null): array
    {
        return $this->searchModel($originalTerm, $maxLength, NotifyCharacter::class, [
            'name_canonical', 'name_english', 'name_japanese', 'name_japanese', 'name_synonyms'
        ]);
    }

    public function searchCompanyByName(string $originalTerm, int $maxLength, $type = null): array
    {
        return $this->searchModel($originalTerm, $maxLength, NotifyCompany::class, [
            'name_english', 'name_japanese', 'name_synonyms'
        ]);
    }

    public function getNotifyAnimeByUniqueID(string $uniqueID, bool $latest = false): object|null
    {
        $query = NotifyAnime::query();

        if ($latest) {
            $query = $query->latest();
        }

        return $query->where('uniqueID', $uniqueID)->first();
    }

    public function getNotifyCompanyById(string $notifyID, bool $latest = false): object|null
    {
        $query = NotifyCompany::query();

        if ($latest) {
            $query = $query->latest();
        }

        return $query->where('notifyID', $notifyID)->first();
    }

    public function getNotifyAnimeCharactersByUniqueId(string $uniqueID): object|null
    {
        return NotifyAnimeCharacter::query()->where('uniqueID', $uniqueID)->first();
    }

    public function getNotifyCharacterById(string $characterId, bool $latest = false): object|null
    {
        $query = NotifyCharacter::query();

        if ($latest) {
            $query = $query->latest();
        }

        return $query->where('notifyID', $characterId)->first();
    }

    public function getNotifyAnimeEpisode(string $uniqueID, int $episodeNumber): object|null
    {
        return MALAnime::query()->where('mal_anime.episode_id', $episodeNumber)
            ->join('notify_anime', 'notify_anime.uniqueID', '=', 'mal_anime.uniqueID')
            ->where('notify_anime.uniqueID', $uniqueID)
            ->select('mal_anime.*')
            ->first();
    }

    public function createOrUpdateMALEpisode($anime, $episode): Model|Builder
    {
        $episode = MALAnime::query()->updateOrCreate([
            'uniqueID' => $anime['uniqueID'],
            'notifyID' => $anime['notifyID'],
            'episode_id' => $episode['mal_id'],
        ], [
            'title' => !empty($episode['title']) ? $episode['title'] : null,
            'title_japanese' => !empty($episode['title_japanese']) ? $episode['title_japanese'] : null,
            'title_romanji' => !empty($episode['title_romanji']) ? $episode['title_romanji'] : null,
            'aired' => !empty($episode['aired']) ? Carbon::parse($episode['aired']) : null,
            'filler' => (int)$episode['filler'],
            'recap' => (int)$episode['recap'],
        ]);

        $episode->touch();

        return $episode;
    }

    public function searchModel(string $originalTerm, int $maxLength, $model, array $searchFields, $type = null): array
    {
        $term = StringOperations::normalizeTerm($originalTerm);

//        $cacheKey = "search_{$model}_{$term}_{$maxLength}_$type";
//        // Return cached results if available
//        if (Cache::has($cacheKey)) {
//            return Cache::get($cacheKey);
//        }

        $results = [];
        $query = $model::query();
        $matchAgainst = 'MATCH(' . implode(', ', $searchFields) . ') AGAINST(? IN NATURAL LANGUAGE MODE)';
        $query->whereRaw($matchAgainst, [$term]);

        foreach ($query->limit(1000)->cursor() as $item) {
            $titles = StringOperations::getNormalizedTitles($item, $searchFields);

            $bestSimilarity = -1;
            $exactMatch = false;

            foreach ($titles as $normalizedTitle => $title) {
                if ($term === $normalizedTitle) {
                    $exactMatch = true;
                    $bestSimilarity = 1;
                    break;
                }
                $similarity = StringOperations::advancedStringSimilarity($term, $normalizedTitle);

                if ($similarity > $bestSimilarity && $similarity >= self::MIN_STRING_SIMILARITY) {
                    $bestSimilarity = $similarity;
                }
            }

            if ($bestSimilarity >= self::MIN_STRING_SIMILARITY) {
                $results[] = (object)[
                    'obj' => $item,
                    'similarity' => $bestSimilarity,
                    'exactMatch' => $exactMatch,
                ];
            }
        }

        usort($results, [$this, 'compareResults']);
        $results = array_slice($results, 0, $maxLength);
        //      Cache::put($cacheKey, $results, self::CACHE_DURATION);

        return $results;
    }

    private function compareResults($a, $b)
    {
        if ($a->exactMatch !== $b->exactMatch) {
            return $b->exactMatch - $a->exactMatch;
        }
        return $b->similarity <=> $a->similarity;
    }
}
