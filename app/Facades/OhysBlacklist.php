<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static isBlacklistedTitle(mixed $title)
 */
class OhysBlacklist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'OhysBlacklist';
    }
}
