<?php

namespace App\Helpers;

use App\Facades\SHA256Hasher;
use App\Models\AccessToken;

class AuthHelper
{
    public function authAccessToken($token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
