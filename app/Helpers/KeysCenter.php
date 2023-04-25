<?php

namespace App\Helpers;

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
}
