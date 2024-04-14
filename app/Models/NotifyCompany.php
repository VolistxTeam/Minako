<?php

namespace App\Models;

use App\Classes\StringCompareJaroWinkler;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifyCompany extends Model
{
    use HasFactory;
    use ClearsResponseCache;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notify_company';

    protected $fillable = [
        'uniqueID',
        'notifyID',
        'name_english',
        'name_japanese',
        'name_synonyms',
        'description',
        'email',
        'links',
        'mappings',
        'location',
    ];

    protected $casts = [
        'name_synonyms' => 'array',
        'links'         => 'array',
        'mappings'      => 'array',
        'location'      => 'array',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public static function searchByName(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $term = self::normalizeTerm($originalTerm);
        $cacheKey = "minako_search_company_by_name_{$term}_{$maxLength}_{$type}";

//        // Return cached results if available
//        if (Cache::has($cacheKey)) {
//            return Cache::get($cacheKey);
//        }

        $results = [];
        $notifyQuery = self::query();

        // Apply full-text search
        $notifyQuery->whereRaw("MATCH(name_english, name_japanese, name_synonyms) AGAINST(? IN NATURAL LANGUAGE MODE)", [$term]);

        // Consider implementing pagination or batch processing if still too slow
        foreach ($notifyQuery->limit(1000)->cursor() as $item) {
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


    private static function normalizeTerm(string $term): string
    {
        return mb_strtolower($term);
    }

    private static function getNormalizedTitles($item): array
    {
        $titleFields = [
            'name_english', 'name_japanese',
        ];
        $titles = [];

        foreach ($titleFields as $field) {
            if ($item->$field) {
                $titles[self::normalizeTerm($item->$field)] = $item->$field;
            }
        }

        $synonyms = $item->name_synonyms ?? [];

        foreach ($synonyms as $synonym) {
            $normalizedSynonym = self::normalizeTerm($synonym);
            $titles[$normalizedSynonym] = $synonym;
        }

        return $titles;
    }

    private static function advancedStringSimilarity(string $term, string $from): float
    {
        static $comparator = null;
        if (!$comparator) {
            $comparator = new StringCompareJaroWinkler();
        }

        $similarity = $comparator->jaroWinkler($term, $from, 0.7, 6);
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
