<?php

namespace App\Facades;

use App\Helpers\DTOUtilsHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getTitlesDTO($entity)
 * @method static getPosterImageDTO($entity)
 * @method static getRatingDTO($entity)
 * @method static getEpisodeInfoDTO($entity)
 * @method static getMappingDTO($entity)
 * @method static getTrailersDTO($entity)
 */
class DTOUtils extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DTOUtilsHelper::class;
    }
}
