<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OhysRelation extends Model
{
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
    protected $table = 'ohys_relation';
    protected $fillable = [
        'uniqueID',
        'matchingID',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function torrent()
    {
        return $this->belongsTo(OhysTorrent::class, 'uniqueID');
    }

    public function anime()
    {
        return $this->belongsTo(NotifyAnime::class, 'uniqueID', 'matchingID');
    }
}
