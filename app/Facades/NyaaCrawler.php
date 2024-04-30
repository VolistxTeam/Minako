<?php

namespace App\Facades;

use App\Helpers\NyaaCrawlerHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Get(string $large)
 * @method static getAllTorrents()
 */
class NyaaCrawler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return NyaaCrawlerHelper::class;
    }
}
