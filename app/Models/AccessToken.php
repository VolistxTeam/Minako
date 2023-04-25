<?php

namespace App\Models;

use App\Models\Traits\UuidForKey;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use UuidForKey;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'secret',
        'secret_salt',
    ];

    protected $casts = [
        'created_at'    => 'date:Y-m-d H:i:s',
        'updated_at'    => 'date:Y-m-d H:i:s',
    ];
}
