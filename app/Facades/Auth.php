<?php

namespace App\Facades;

use App\Helpers\AuthHelper;
use App\Helpers\HttpClientHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Get(string $large)
 * @method static authAccessToken(string|null $bearerToken)
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthHelper::class;
    }
}
