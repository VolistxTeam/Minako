<?php

namespace App\Models;

use App\Facades\StringOperations;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifyCharacter extends Model
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
    protected $table = 'notify_character';

    protected $fillable = [
        'uniqueID',
        'notifyID',
        'name_canonical',
        'name_english',
        'name_japanese',
        'name_synonyms',
        'image_extension',
        'image_width',
        'image_height',
        'description',
        'spoilers',
        'attributes',
        'mappings',
        'isHidden',
    ];

    protected $casts = [
        'name_synonyms' => 'array',
        'spoilers' => 'array',
        'attributes' => 'array',
        'mappings' => 'array',
        'isHidden' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
