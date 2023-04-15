<?php

namespace App\Helpers;

use App\Repositories\OhysBlacklistTitleRepository;
use Illuminate\Container\Container;
use RandomLib\Factory;
use SecurityLib\Strength;

class OhysBlacklistCenter
{
    private OhysBlacklistTitleRepository $ohysBlacklistTitleRepository;
    private array $blacklistedTitles;
    public function __construct()
    {
        $this->ohysBlacklistTitleRepository = Container::getInstance()->make(OhysBlacklistTitleRepository::class);
        $this->blacklistedTitles = $this->ohysBlacklistTitleRepository->FindAll();
    }

    public function isBlacklistedTitle(string $title): string
    {
        $lowercaseTitle = strtolower($title);
        foreach ($this->blacklistedTitles as $blacklistedTitle) {
            if (str_contains($lowercaseTitle, $blacklistedTitle->name)) {
                return true;
            }
        }

        return false;
    }
}
