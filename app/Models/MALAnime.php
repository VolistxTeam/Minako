<?php

namespace App\Models;

use App\Classes\StringCompareJaroWinkler;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MALAnime extends Model
{
    use ClearsResponseCache;
    use HasFactory;

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
    protected $table = 'mal_anime';

    protected $fillable = [
        'uniqueID',
        'notifyID',
        'episode_id',
        'title',
        'title_japanese',
        'title_romanji',
        'aired',
        'filler',
        'recap',
        'isHidden',
    ];

    protected $casts = [
        'aired' => 'datetime:Y-m-d H:i:s',
        'filler' => 'boolean',
        'recap' => 'boolean',
        'isHidden' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function anime()
    {
        return $this->belongsTo(NotifyAnime::class, 'uniqueID');
    }

    public static function searchByName(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $term = self::normalizeTerm($originalTerm);
        $cacheKey = "minako_search_episode_by_name_{$term}_{$maxLength}_{$type}";

        //        // Return cached results if available
        //        if (Cache::has($cacheKey)) {
        //            return Cache::get($cacheKey);
        //        }

        $results = [];
        $notifyQuery = self::query();

        // Apply full-text search
        $notifyQuery->whereRaw('MATCH(title, title_japanese, title_romanji) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);

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
                $results[] = (object) [
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
            'title', 'title_japanese', 'title_romanji',
        ];
        $titles = [];

        foreach ($titleFields as $field) {
            if ($item->$field) {
                $titles[self::normalizeTerm($item->$field)] = $item->$field;
            }
        }

        return $titles;
    }

    private static function advancedStringSimilarity(string $term, string $from): float
    {
        static $comparator = null;
        if (! $comparator) {
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
