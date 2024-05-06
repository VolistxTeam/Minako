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

    public function getNormalizedTitles($item, $titleFields): array
    {
        $titles = [];

        //handle normal fields
        foreach ($titleFields as $field) {
            if ($item->$field && is_string($item->$field)) {
                $titles[$this->normalizeTerm($item->$field)] = $item->$field;
            }
        }

        //handle array fields
        foreach ($titleFields as $arrayField) {
            if ($item->$arrayField && is_array($item->$arrayField)) {
                foreach ($item->$arrayField as $term) {
                    $normalizedSynonym = $this->normalizeTerm($term);
                    $titles[$normalizedSynonym] = $term;
                }
            }
        }

        return $titles;
    }

    public function normalizeTerm(string $term): string
    {
        return mb_strtolower($term);
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
