<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static randomSaltedKey()
 * @method static randomKey()
 * @method static authAccessToken($token)
 */
class Keys extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Keys';
    }
}
