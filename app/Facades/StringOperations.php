<?php

namespace App\Facades;

use App\Helpers\StringOperationsHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static normalizeTerm(string $originalTerm)
 * @method static getNormalizedTitles($item, string[] $array)
 * @method static advancedStringSimilarity(mixed $term, int|string $normalizedTitle)
 */
class StringOperations extends Facade
{
    protected static function getFacadeAccessor()
    {
        return StringOperationsHelper::class;
    }
}
