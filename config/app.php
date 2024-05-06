<?php

use App\Facades\Auth;
use App\Facades\DTOUtils;
use App\Facades\HttpClient;
use App\Facades\JikanAPI;
use App\Facades\NyaaCrawler;
use App\Facades\OhysBlacklist;
use App\Facades\StringOperations;

return [
    'aliases' => [
        'StringOperations' => StringOperations::class,
        'HttpClient' => HttpClient::class,
        'Auth' => Auth::class,
        'OhysBlacklist' => OhysBlacklist::class,
        'NyaaCrawler' => NyaaCrawler::class,
        'JikanAPI' => JikanAPI::class,
        'DTOUtils' => DTOUtils::class,
    ],
];
