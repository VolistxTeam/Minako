<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class OhysRelation extends Model
{
    use HasFactory, Searchable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ohys_relation';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'uniqueID',
        'matchingID',
    ];

    protected $casts = [
        'created_at'  => 'date:Y-m-d H:i:s',
        'updated_at'  => 'date:Y-m-d H:i:s',
    ];

    public function torrent()
    {
        return $this->belongsTo(OhysTorrent::class, 'uniqueID');
    }

    public function anime()
    {
        return $this->belongsTo(NotifyAnime::class, 'uniqueID', 'matchingID');
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
            'matchingID' => $this->matchingID,
        ];
    }
}
