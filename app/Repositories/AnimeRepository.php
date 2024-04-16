<?php

namespace App\Repositories;


use App\Facades\StringOperations;
use App\Models\MALAnime;
use App\Models\NotifyAnime;

class AnimeRepository
{
    const MIN_STRING_SIMILARITY = 0.89;

    public function searchByTitle(string $originalTerm, int $maxLength, $type = null): array
    {
        $term = StringOperations::normalizeTerm($originalTerm);

        $cacheKey = "minako_search_anime_by_title_{$term}_{$maxLength}_{$type}";

//        // Return cached results if available
//        if (Cache::has($cacheKey)) {
//            return Cache::get($cacheKey);
//        }

        $results = [];
        $notifyQuery = NotifyAnime::query();

        // Apply full-text search
        $notifyQuery->whereRaw('MATCH(title_canonical, title_english, title_romaji, title_japanese, title_hiragana, title_synonyms) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);

        if (!empty($type)) {
            $notifyQuery->where('type', $type);
        }

        // Consider implementing pagination or batch processing if still too slow
        foreach ($notifyQuery->limit(1000)->cursor() as $item) {
            $titles = StringOperations::getNormalizedTitles($item, [
                'title_canonical', 'title_english', 'title_romaji', 'title_japanese', 'title_hiragana',
            ]);

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

        usort($results, function ($a, $b) {
            if ($a->exactMatch !== $b->exactMatch) {
                return $b->exactMatch - $a->exactMatch;
            }
            return $b->similarity <=> $a->similarity;
        });

        $results = array_slice($results, 0, $maxLength);

        // Cache the results for 1 hour
//        Cache::put($cacheKey, $results, 3600);

        return $results;
    }

    public function searchByName(string $originalTerm, int $maxLength, $type = null): array
    {
        $term = StringOperations::normalizeTerm($originalTerm);
        $cacheKey = "minako_search_episode_by_name_{$term}_{$maxLength}_{$type}";

        //        // Return cached results if available
        //        if (Cache::has($cacheKey)) {
        //            return Cache::get($cacheKey);
        //        }

        $results = [];
        $notifyQuery = MALAnime::query();

        // Apply full-text search
        $notifyQuery->whereRaw('MATCH(title, title_japanese, title_romanji) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);

        // Consider implementing pagination or batch processing if still too slow
        foreach ($notifyQuery->limit(1000)->cursor() as $item) {
            $titles = StringOperations::getNormalizedTitles($item, [
                'title', 'title_japanese', 'title_romanji',
            ]);
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

        usort($results, function ($a, $b) {
            if ($a->exactMatch !== $b->exactMatch) {
                return $b->exactMatch - $a->exactMatch;
            }

            return $b->similarity <=> $a->similarity;
        });

        $results = array_slice($results, 0, $maxLength);

        // Cache the results for 1 hour
        //        Cache::put($cacheKey, $results, 3600);

        return $results;
    }
}
