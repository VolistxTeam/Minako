<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class NotifyCompany extends Model
{
    use HasFactory;
    use Searchable;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true; // cache time, in seconds
    public $cacheFor = 3600;
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

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id'            => $this->id,
            'uniqueID'      => $this->uniqueID,
            'name_english'  => $this->name_english,
            'name_japanese' => $this->name_japanese,
            'name_synonyms' => empty($this->name_synonyms) ? '' : implode('|', $this->name_synonyms),
        ];
    }
}
