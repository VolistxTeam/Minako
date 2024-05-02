<?php

namespace App\Models;

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
}
