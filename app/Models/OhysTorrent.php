<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class OhysTorrent extends Model
{
    use HasFactory, Searchable, HasRelationships, QueryCacheable;

    public $cacheFor = 3600; // cache time, in seconds

    protected static $flushCacheOnUpdate = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ohys_torrent';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'uniqueID',
        'releaseGroup',
        'title',
        'episode',
        'torrentName',
        'info_totalHash',
        'info_totalSize',
        'info_createdDate',
        'info_torrent_announces',
        'info_torrent_files',
        'metadata_video_resolution',
        'metadata_video_codec',
        'metadata_audio_codec',
        'hidden_download_magnet',
    ];

    protected $casts = [
        'info_torrent_announces' => 'array',
        'info_torrent_files' => 'array',
        'created_at'  => 'date:Y-m-d H:i:s',
        'updated_at'  => 'date:Y-m-d H:i:s',
    ];

    public function anime()
    {
        return $this->hasOneDeep('App\Models\NotifyAnime', ['App\Models\OhysRelation'], ['uniqueID', 'uniqueID'], ['uniqueID', 'matchingID']);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray() {
        return [
            'id' => $this->id,
            'uniqueID' => $this->uniqueID,
            'title' => $this->title,
            'torrentName' => $this->torrentName,
        ];
    }
}
