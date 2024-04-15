<?php

namespace App\Facades;

use App\Helpers\StringOperationsHelper;
use Illuminate\Support\Facades\Facade;

class StringOperations extends Facade
{
    protected static function getFacadeAccessor()
    {
        return StringOperationsHelper::class;
    }
}
