<?php

namespace App\Helpers;

use App\Repositories\OhysBlacklistTitleRepository;
use Illuminate\Container\Container;

class OhysBlacklistCenter
{
    private OhysBlacklistTitleRepository $ohysBlacklistTitleRepository;
    private array $titles;

    public function __construct()
    {
        $this->ohysBlacklistTitleRepository = Container::getInstance()->make(OhysBlacklistTitleRepository::class);
        $this->titles = $this->ohysBlacklistTitleRepository->FindTitles();
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
