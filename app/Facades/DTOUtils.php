<?php

namespace App\Facades;

use App\Helpers\DTOUtilsHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getTitlesDTO($entity)
 * @method static getImageDTO($entity, $key)
 * @method static getRatingDTO($entity)
 * @method static getEpisodeInfoDTO($entity)
 * @method static getMappingDTO($entity)
 * @method static getTrailersDTO($entity)
 * @method static getNamesDTO($entity)
 * @method static getSanitizedTitlesDTO($entity)
 * @method static getInfoDTO($entity)
 * @method static getAnnouncesDTO($entity)
 * @method static getMetadataDTO($entity)
 * @method static getDownloadLinksDTO($entity, string $string)
 */
class DTOUtils extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DTOUtilsHelper::class;
    }
}
