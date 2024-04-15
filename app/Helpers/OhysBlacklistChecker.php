<?php

namespace App\Helpers;

use App\Models\OhysBlacklistTitle;

class OhysBlacklistChecker
{
    private static array $titles = [];

    // Static initializer to load titles
    public static function loadTitles(): void
    {
        self::$titles = OhysBlacklistTitle::query()->pluck('name')->toArray();
    }

    public static function isBlacklistedTitle(string $title): bool
    {
        $lowercaseTitle = strtolower($title);

        foreach (self::$titles as $blacklistedTitle) {
            if (str_contains($lowercaseTitle, strtolower($blacklistedTitle))) {
                return true;
            }
        }

        return false;
    }
}
