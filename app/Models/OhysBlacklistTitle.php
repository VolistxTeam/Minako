<?php

namespace App\Models;

use App\Models\Traits\UuidForKey;
use Illuminate\Database\Eloquent\Model;

class OhysBlacklistTitle extends Model
{
    use UuidForKey;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $table = 'ohys_blacklist';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'is_active',
        'reason'
    ];

    protected $casts = [
        'created_at'    => 'date:Y-m-d H:i:s',
        'updated_at'    => 'date:Y-m-d H:i:s',
        'is_active'     => 'boolean'
    ];
}
