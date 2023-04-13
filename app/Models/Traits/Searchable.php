<?php

namespace App\Models\Traits;

use App\Classes\StringCompareJaroWinkler;

trait Searchable
{
    public static function searchByTitle(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $results = [];

        $term = self::normalizeTerm($originalTerm);

        $notifyQuery = self::query();

        if (!empty($type)) {
            $notifyQuery->where('type', $type);
        }

        foreach ($notifyQuery->cursor() as $item) {
            $result = [];

            $titleCanonical = $item->title_canonical ? self::normalizeTerm($item->title_canonical) : '';
            $titleEnglish = $item->title_english ? self::normalizeTerm($item->title_english) : '';
            $titleRomaji = $item->title_romaji ? self::normalizeTerm($item->title_romaji) : '';

            $synonyms = $item->title_synonyms ?? [];

            $titles = [
                $titleCanonical => $item->title_canonical,
                $titleEnglish   => $item->title_english,
                $titleRomaji    => $item->title_romaji,
            ];

            foreach ($synonyms as $synonym) {
                $normalizedSynonym = $synonym ? self::normalizeTerm($synonym) : '';
                $titles[$normalizedSynonym] = $synonym;
            }

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
                $result = (object) [
                    'obj'        => $item,
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

        return array_slice($results, 0, $maxLength);
    }
    private static function normalizeTerm(string $term): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $term));
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
