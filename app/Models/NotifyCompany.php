<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class NotifyCompany extends Model
{
    use HasFactory, Searchable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notify_company';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

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
        'created_at'  => 'date:Y-m-d H:i:s',
        'updated_at'  => 'date:Y-m-d H:i:s',
    ];

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
            'name_english' => $this->name_english,
            'name_japanese' => $this->name_japanese,
            'name_synonyms' => empty($this->name_synonyms) ? '' : implode('|', $this->name_synonyms),
        ];
    }
}
