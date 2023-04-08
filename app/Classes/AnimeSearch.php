<?php

namespace App\Classes;

use App\Models\NotifyAnime;

class AnimeSearch
{
    public function searchByTitle(string $originalTerm, int $maxLength): array
    {
        $minStringSimilarity = 0.89;
        $results = [];

        $term = $this->normalizeTerm($originalTerm);

        foreach (NotifyAnime::query()->cursor() as $item) {
            $result = [];

            $titleCanonical = $item->title_canonical ? $this->normalizeTerm($item->title_canonical) : '';
            $titleEnglish = $item->title_english ? $this->normalizeTerm($item->title_english) : '';
            $titleRomaji = $item->title_romaji ? $this->normalizeTerm($item->title_romaji) : '';

            if ($item->title_synonyms == null) {
                $synonyms = [];
            } else {
                $synonyms = $item->title_synonyms;
            }

            $titles = [
                $titleCanonical => $item->title_canonical,
                $titleEnglish   => $item->title_english,
                $titleRomaji    => $item->title_romaji,
            ];

            foreach ($synonyms as $synonym) {
                $normalizedSynonym = $synonym ? $this->normalizeTerm($synonym) : '';
                $titles[$normalizedSynonym] = $synonym;
            }

            $bestSimilarity = -1;
            foreach ($titles as $normalizedTitle => $title) {
                $similarity = $this->advancedStringSimilarity($term, $normalizedTitle);

                if ($similarity > $bestSimilarity && $similarity >= $minStringSimilarity) {
                    $bestSimilarity = $similarity;

                    $result = (object) [
                        'obj'        => $item,
                        'similarity' => $similarity,
                    ];
                }
            }

            if (!empty($result)) {
                $results[] = $result;
            }
        }

        usort($results, function ($a, $b) {
            return $b->similarity <=> $a->similarity;
        });

        return array_slice($results, 0, $maxLength);
    }

    private function normalizeTerm(string $term): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $term));
    }

    private function advancedStringSimilarity(string $term, string $from): float
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
