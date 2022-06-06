<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class NotifyCharacterRelation extends Model
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
    protected $table = 'notify_character_relation';
    protected $fillable = [
        'uniqueID',
        'notifyID',
        'items',
    ];

    protected $casts = [
        'items'      => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id'       => $this->id,
            'uniqueID' => $this->uniqueID,
        ];
    }
}
