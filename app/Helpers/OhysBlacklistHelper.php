<?php

namespace App\Helpers;

use App\Repositories\OhysBlacklistRepository;

class OhysBlacklistHelper
{
    private array $titles = [];

    private OhysBlacklistRepository $blacklistRepository;

    public function __construct()
    {
        $this->blacklistRepository = new OhysBlacklistRepository();
        $this->titles = $this->blacklistRepository->FindAllBlacklistedNames();
    }

    public function isBlacklistedTitle(string $title): bool
    {
        $lowercaseTitle = strtolower($title);

        $lowercaseBlacklistedTitles = array_map('strtolower', $this->titles);

        foreach ($lowercaseBlacklistedTitles as $blacklistedTitle) {
            if (str_contains($lowercaseTitle, $blacklistedTitle)) {
                return true;
            }
        }

        return false;
    }
}
