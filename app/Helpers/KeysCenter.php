<?php

namespace App\Helpers;

use App\Models\AccessToken;
use Illuminate\Support\Str;
use RandomLib\Factory;
use SecurityLib\Strength;

class KeysCenter
{
    public static function randomKey(int $length = 64): string
    {
        return Str::random($length);
    }

    public static function randomSaltedKey(int $keyLength = 64, int $saltLength = 16): array
    {
        return [
            'key'  => self::randomKey($keyLength),
            'salt' => self::randomKey($saltLength),
        ];
    }

    public static function authAccessToken($token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
