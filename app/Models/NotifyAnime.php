<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class NotifyAnime extends Model
{
    use HasFactory, Searchable, HasRelationships;

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
        'isHidden'
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
        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
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
        return $this->hasMany(NotifyCharacterRelation::class, 'uniqueID', 'uniqueID');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'uniqueID' => $this->uniqueID,
            'title_canonical' => $this->title_canonical,
            'title_romaji' => $this->title_romaji,
            'title_english' => $this->title_english,
            'title_japanese' => $this->title_japanese,
            'title_hiragana' => $this->title_hiragana,
            'title_synonyms' => empty($this->title_synonyms) ? '' : implode("|", $this->title_synonyms)
        ];
    }
}
