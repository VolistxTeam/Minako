<?php

namespace App\Helpers;

use App\Models\OhysBlacklistTitle;

class OhysBlacklistCenter
{
    private array $titles;

    public function __construct()
    {
        $this->titles = OhysBlacklistTitle::query()->pluck('name')->toArray();
    }

    public function isBlacklistedTitle(string $title): string
    {
        $lowercaseTitle = strtolower($title);

        foreach ($this->titles as $blacklistedTitle) {
            if (str_contains($lowercaseTitle, strtolower($blacklistedTitle))) {
                return true;
            }
        }

        return false;
    }
}
