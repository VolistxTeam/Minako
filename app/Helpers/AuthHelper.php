<?php

namespace App\Helpers;

use App\Models\AccessToken;
use Illuminate\Support\Str;

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
