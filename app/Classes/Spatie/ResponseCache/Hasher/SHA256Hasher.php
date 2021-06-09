<?php

namespace App\Classes\Spatie\ResponseCache\Hasher;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Hasher\RequestHasher;

class SHA256Hasher implements RequestHasher
{
    public function __construct(
        protected CacheProfile $cacheProfile,
    ) {
        //
    }

    public function getHashFor(Request $request): string
    {
        return 'minako-caching:' . hash('md5', "{$request->getHost()}-{$request->getRequestUri()}-{$request->getMethod()}/".$this->cacheProfile->useCacheNameSuffix($request));
    }
}
