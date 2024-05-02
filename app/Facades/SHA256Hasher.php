<?php

namespace App\Facades;

use App\Helpers\SHA256HasherHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Get(string $large)
 * @method static getAllTorrents()
 * @method static check($substr, $secret, $array)
 */
class SHA256Hasher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SHA256HasherHelper::class;
    }
}
