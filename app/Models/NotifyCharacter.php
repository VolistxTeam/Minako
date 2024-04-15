<?php

namespace App\Models;

use App\Facades\StringOperations;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifyCharacter extends Model
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
    protected $table = 'notify_character';

    protected $fillable = [
        'uniqueID',
        'notifyID',
        'name_canonical',
        'name_english',
        'name_japanese',
        'name_synonyms',
        'image_extension',
        'image_width',
        'image_height',
        'description',
        'spoilers',
        'attributes',
        'mappings',
        'isHidden',
    ];

    protected $casts = [
        'name_synonyms' => 'array',
        'spoilers' => 'array',
        'attributes' => 'array',
        'mappings' => 'array',
        'isHidden' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public static function searchByName(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $term = StringOperations::normalizeTerm($originalTerm);
        $cacheKey = "minako_search_character_by_name_{$term}_{$maxLength}_{$type}";

//        // Return cached results if available
//        if (Cache::has($cacheKey)) {
////            return Cache::get($cacheKey);
//        }

        $results = [];
        $notifyQuery = self::query();

        // Apply full-text search
        $notifyQuery->whereRaw('MATCH(name_canonical, name_english, name_japanese, name_synonyms) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);

        // Consider implementing pagination or batch processing if still too slow
        foreach ($notifyQuery->limit(1000)->cursor() as $item) {
            $titles = StringOperations::getNormalizedTitles($item, [
                'name_canonical', 'name_english', 'name_japanese',
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
}
