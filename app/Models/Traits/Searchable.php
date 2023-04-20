<?php

namespace App\Models\Traits;

use App\Classes\StringCompareJaroWinkler;
use Illuminate\Support\Facades\Cache;

trait Searchable
{
    public static function searchByTitle(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $term = self::normalizeTerm($originalTerm);
        $cacheKey = "search_by_title_{$term}_{$maxLength}_{$type}";

        // Return cached results if available
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $results = [];
        $notifyQuery = self::query();

        if (!empty($type)) {
            $notifyQuery->where('type', $type);
        }

        $notifyQuery->where(function ($query) use ($term) {
            $query->where('title_canonical', 'like', "%{$term}%")
                ->orWhere('title_english', 'like', "%{$term}%")
                ->orWhere('title_romaji', 'like', "%{$term}%")
                ->orWhere('title_japanese', 'like', "%{$term}%")
                ->orWhere('title_hiragana', 'like', "%{$term}%")
                ->orWhereJsonContains('title_synonyms', $term);
        });

        foreach ($notifyQuery->cursor() as $item) {
            $titles = self::getNormalizedTitles($item);
            $bestSimilarity = -1;
            $exactMatch = false;

            foreach ($titles as $normalizedTitle => $title) {
                if ($term === $normalizedTitle) {
                    $exactMatch = true;
                    $bestSimilarity = 1;
                    break;
                }
                $similarity = self::advancedStringSimilarity($term, $normalizedTitle);

                if ($similarity > $bestSimilarity && $similarity >= $minStringSimilarity) {
                    $bestSimilarity = $similarity;
                }
            }

            if ($bestSimilarity >= $minStringSimilarity) {
                $result = (object)[
                    'obj' => $item,
                    'similarity' => $bestSimilarity,
                    'exactMatch' => $exactMatch,
                ];
                $results[] = $result;
            }
        }

        usort($results, function ($a, $b) {
            if ($a->exactMatch === $b->exactMatch) {
                return $b->similarity <=> $a->similarity;
            }
            return $b->exactMatch <=> $a->exactMatch;
        });

        $results = array_slice($results, 0, $maxLength);

        // Cache the results for 1 hour
        Cache::put($cacheKey, $results, 60 * 60);

        return $results;
    }

    private static function normalizeTerm(string $term): string
    {
        return mb_strtolower($term);
    }

    private static function getNormalizedTitles($item): array
    {
        $titleFields = [
            'title_canonical',
            'title_english',
            'title_romaji',
            'title_japanese',
            'title_hiragana',
        ];
        $titles = [];

        foreach ($titleFields as $field) {
            if ($item->$field) {
                $titles[self::normalizeTerm($item->$field)] = $item->$field;
            }
        }

        $synonyms = $item->title_synonyms ?? [];

        foreach ($synonyms as $synonym) {
            $normalizedSynonym = self::normalizeTerm($synonym);
            $titles[$normalizedSynonym] = $synonym;
        }

        return $titles;
    }

    private static function advancedStringSimilarity(string $term, string $from): float
    {
        if ($term == $from) {
            return 10000000;
        }
        $s = new StringCompareJaroWinkler();
        $s = $s->jaroWinkler($term, $from, 0.7, 6);

        if (str_contains($from, $term)) {
            $s += 0.6;
            if (str_starts_with($from, $term)) {
                $s += 0.4;
            }
        }

        return $s;
    }
}
