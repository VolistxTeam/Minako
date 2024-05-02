<?php

namespace App\Models;

use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifyCompany extends Model
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
        'links' => 'array',
        'mappings' => 'array',
        'location' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
