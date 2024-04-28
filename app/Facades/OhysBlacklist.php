<?php

namespace App\Facades;

use App\Helpers\OhysBlacklistHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static isBlacklistedTitle(mixed $param)
 */
class OhysBlacklist extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OhysBlacklistHelper::class;
    }
}
