<?php

namespace App\Facades;

use App\Helpers\JikanAPIHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static getAnimeEpisodes($malID)
 */
class JikanAPI extends Facade
{
    protected static function getFacadeAccessor()
    {
        return JikanAPIHelper::class;
    }
}
