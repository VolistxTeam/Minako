<?php

namespace App\Facades;

use App\Helpers\HttpClientHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Get(string $large)
 */
class HttpClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HttpClientHelper::class;
    }
}
