<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class MALAnime extends Model
{
    use HasFactory, Searchable, QueryCacheable;

    public $cacheFor = 3600; // cache time, in seconds

    protected static $flushCacheOnUpdate = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mal_anime';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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
        'isHidden'
    ];

    protected $casts = [
        'aired' => 'boolean',
        'filler' => 'boolean',
        'recap' => 'boolean',
        'isHidden' => 'boolean',
        'created_at'  => 'date:Y-m-d H:i:s',
        'updated_at'  => 'date:Y-m-d H:i:s',
    ];

    public function anime()
    {
        return $this->belongsTo(NotifyAnime::class, 'uniqueID');
    }
}
