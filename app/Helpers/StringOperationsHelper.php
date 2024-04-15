<?php

namespace App\Helpers;

use App\Classes\StringCompareJaroWinkler;

class StringOperationsHelper
{
    private StringCompareJaroWinkler $comparer;

    public function __construct(StringCompareJaroWinkler $comparer)
    {
        $this->comparer = $comparer;
    }

    public function normalizeTerm(string $term): string
    {
        return mb_strtolower($term);
    }

    public function getNormalizedTitles($item, $titleFields): array
    {
        $titles = [];

        foreach ($titleFields as $field) {
            if ($item->$field) {
                $titles[$this->normalizeTerm($item->$field)] = $item->$field;
            }
        }

        $synonyms = $item->title_synonyms ?? [];

        foreach ($synonyms as $synonym) {
            $normalizedSynonym = $this->normalizeTerm($synonym);
            $titles[$normalizedSynonym] = $synonym;
        }

        return $titles;
    }

    public function advancedStringSimilarity(string $term, string $from): float
    {
        $similarity = $this->comparer->jaroWinkler($term, $from, 0.7, 6);
        if ($similarity > 0.85) {  // Only check substrings if similarity is high
            if (str_contains($from, $term)) {
                $similarity += 0.6;
                if (str_starts_with($from, $term)) {
                    $similarity += 0.4;
                }
            }
        }

        return $similarity;
    }
}
