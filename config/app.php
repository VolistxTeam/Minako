<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use App\Facades\Auth;
use App\Facades\DTOUtils;
use App\Facades\HttpClient;
use App\Facades\JikanAPI;
use App\Facades\NyaaCrawler;
use App\Facades\OhysBlacklist;
use App\Facades\StringOperations;

return [

    'aliases' => Facade::defaultAliases()->merge([
        'Auth' => Auth::class,
        'DTOUtils' => DTOUtils::class,
        'HttpClient' => HttpClient::class,
        'JikanAPI' => JikanAPI::class,
        'NyaaCrawler' => NyaaCrawler::class,
        'OhysBlacklist' => OhysBlacklist::class,
        'StringOperations' => StringOperations::class,
    ])->toArray(),

];
