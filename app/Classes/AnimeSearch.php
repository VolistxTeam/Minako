<?php

namespace App\Classes;

use App\Models\NotifyAnime;

class AnimeSearch
{
    protected static array $searchArray = [];

    public function SearchByTitle($originalTerm, $maxLength): array
    {
        global $searchArray;

        if (empty($searchArray)) {
            $searchArray = NotifyAnime::query()->select('uniqueID', 'title_canonical', 'title_english', 'title_romaji', 'title_synonyms', 'status')->get()->toArray();
        }

        $MinStringSimilarity = 0.89;
        $results = [];

        $term = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $originalTerm));

        foreach ($searchArray as $item) {
            $result = [];
            if ($item['title_canonical'] == $originalTerm) {
                array_push($result, (object) [
                    'obj'        => $item,
                    'similarity' => 99999999,
                ]);

                $keys = array_column($result, 'similarity');
                array_multisort($keys, SORT_DESC, $result);

                if (!empty($result)) {
                    array_push($results, $result[0]);
                    continue;
                }
            }

            if ($term == strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $item['title_canonical']))) {
                array_push($result, (object) [
                    'obj'        => $item,
                    'similarity' => 99999998,
                ]);

                $keys = array_column($result, 'similarity');
                array_multisort($keys, SORT_DESC, $result);

                if (!empty($result)) {
                    array_push($results, $result[0]);
                    continue;
                }
            }

            $similarity = $this->AdvancedStringSimilarity($term, strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $item['title_canonical'])));

            if ($similarity >= $MinStringSimilarity) {
                if ($item['status'] != 'TV' && $item['status'] != 'Movie') {
                    $similarity -= 0.3;
                }

                array_push($result, (object) [
                    'obj'        => $item,
                    'similarity' => $similarity,
                ]);
            }

            if (!empty($item['synonyms'])) {
                foreach ($item['synonyms'] as $synonym) {
                    if ($synonym == $originalTerm) {
                        array_push($result, (object) [
                            'obj'        => $item,
                            'similarity' => 1,
                        ]);
                    }

                    $similarity = $this->AdvancedStringSimilarity($originalTerm, strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $synonym)));

                    if ($similarity >= $MinStringSimilarity) {
                        if ($item['status'] != 'TV' && $item['status'] != 'Movie') {
                            $similarity -= 0.3;
                        }

                        array_push($result, (object) [
                            'obj'        => $item,
                            'similarity' => $similarity,
                        ]);
                    }
                }
            }

            $similarity = $this->AdvancedStringSimilarity($term, strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $item['title_english'])));

            if ($similarity >= $MinStringSimilarity) {
                if ($item['status'] != 'TV' && $item['status'] != 'Movie') {
                    $similarity -= 0.3;
                }

                array_push($result, (object) [
                    'obj'        => $item,
                    'similarity' => $similarity,
                ]);
            }

            $similarity = $this->AdvancedStringSimilarity($term, strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $item['title_romaji'])));

            if ($similarity >= $MinStringSimilarity) {
                if ($item['status'] != 'TV' && $item['status'] != 'Movie') {
                    $similarity -= 0.3;
                }

                array_push($result, (object) [
                    'obj'        => $item,
                    'similarity' => $similarity,
                ]);
            }

            $keys = array_column($result, 'similarity');
            array_multisort($keys, SORT_DESC, $result);

            if (!empty($result)) {
                array_push($results, $result[0]);
            }
        }

        $keys = array_column($results, 'similarity');
        array_multisort($keys, SORT_DESC, $results);

        return array_slice($results, 0, $maxLength);
    }

    private function AdvancedStringSimilarity($term, $from): float
    {
        if ($term == $from) {
            return 10000000;
        }

        $normalizedTerm = preg_replace('/[^a-zA-Z0-9]+/', '', $term);
        $normalizedFrom = preg_replace('/[^a-zA-Z0-9]+/', '', $from);

        if ($normalizedTerm == $normalizedFrom) {
            return 100000;
        }

        $s = new StringCompareJaroWinkler();
        $s = $s->JaroWinkler($term, $from, 0.7, 6);

        if (str_contains($normalizedFrom, $normalizedTerm)) {
            $s += 0.6;

            if (str_starts_with($from, $term)) {
                $s += 0.4;
            }
        }

        return $s;
    }
}
