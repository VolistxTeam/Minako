<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class NotifyCharacter extends Model
{
    use HasFactory, Searchable, QueryCacheable;

    public $cacheFor = 3600; // cache time, in seconds

    protected static $flushCacheOnUpdate = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notify_character';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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
        'isHidden'
    ];

    protected $casts = [
        'name_synonyms' => 'array',
        'spoilers' => 'array',
        'attributes' => 'array',
        'mappings' => 'array',
        'isHidden' => 'boolean',
        'created_at'  => 'date:Y-m-d H:i:s',
        'updated_at'  => 'date:Y-m-d H:i:s',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray() {
        return [
            'id' => $this->id,
            'uniqueID' => $this->uniqueID,
            'name_canonical' => $this->name_canonical,
            'name_english' => $this->name_english,
            'name_japanese' => $this->name_japanese,
            'name_synonyms' => empty($this->name_synonyms) ? '' : implode("|", $this->name_synonyms)
        ];
    }
}
