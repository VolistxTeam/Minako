<?php

use Illuminate\Support\Facades\Facade;

return [
    'aliases' => Facade::defaultAliases()->merge([
        'StringOperations' => App\Facades\StringOperations::class,
        'HttpClient' => App\Facades\HttpClient::class,
        'Auth' => App\Facades\Auth::class,
        'OhysBlacklist' => App\Facades\OhysBlacklist::class,
        'NyaaCrawler' => App\Facades\NyaaCrawler::class,
        'JikanAPI' => App\Facades\JikanAPI::class,
        'DTOUtils' => App\Facades\DTOUtils::class,
    ]),
];
