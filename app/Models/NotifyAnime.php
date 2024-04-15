<?php

namespace App\Models;

use App\Facades\StringOperations;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class NotifyAnime extends Model
{
    use ClearsResponseCache;
    use HasFactory;
    use HasRelationships;

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
    protected $table = 'notify_anime';

    protected $fillable = [
        'uniqueID',
        'notifyID',
        'type',
        'title_canonical',
        'title_romaji',
        'title_english',
        'title_japanese',
        'title_hiragana',
        'title_synonyms',
        'summary',
        'status',
        'genres',
        'startDate',
        'endDate',
        'episodeCount',
        'episodeLength',
        'source',
        'image_extension',
        'image_width',
        'image_height',
        'firstChannel',
        'rating_overall',
        'rating_story',
        'rating_visuals',
        'rating_soundtrack',
        'trailers',
        'n_episodes',
        'mappings',
        'studios',
        'producers',
        'licensors',
        'isHidden',
    ];

    protected $casts = [
        'title_synonyms' => 'array',
        'genres' => 'array',
        'trailers' => 'array',
        'n_episodes' => 'array',
        'mappings' => 'array',
        'studios' => 'array',
        'producers' => 'array',
        'licensors' => 'array',
        'isHidden' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function torrents()
    {
        return $this->hasManyDeep('App\Models\OhysTorrent', ['App\Models\OhysRelation'], ['matchingID', 'uniqueID'], ['uniqueID', 'uniqueID']);
    }

    public function relations()
    {
        return $this->hasOne(NotifyRelation::class, 'uniqueID', 'uniqueID');
    }

    public function studios()
    {
        return $this->hasMany(NotifyCompany::class, 'notifyID', 'notifyID');
    }

    public function episodes()
    {
        return $this->hasMany(MALAnime::class, 'uniqueID', 'uniqueID')->orderBy('episode_id');
    }

    public function characters()
    {
        return $this->hasMany(NotifyAnimeCharacter::class, 'uniqueID', 'uniqueID');
    }

    public static function searchByTitle(string $originalTerm, int $maxLength, $type = null): array
    {
        $minStringSimilarity = 0.89;
        $term = StringOperations::normalizeTerm($originalTerm);
        $cacheKey = "minako_search_anime_by_title_{$term}_{$maxLength}_{$type}";

//        // Return cached results if available
//        if (Cache::has($cacheKey)) {
//            return Cache::get($cacheKey);
//        }

        $results = [];
        $notifyQuery = self::query();

        // Apply full-text search
        $notifyQuery->whereRaw('MATCH(title_canonical, title_english, title_romaji, title_japanese, title_hiragana, title_synonyms) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);

        if (! empty($type)) {
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
