<?php

namespace App\Helpers;

use App\Repositories\OhysBlacklistTitleRepository;
use Illuminate\Container\Container;
use RandomLib\Factory;
use SecurityLib\Strength;

class OhysBlacklistCenter
{
    private OhysBlacklistTitleRepository $ohysBlacklistTitleRepository;
    public function __construct()
    {
        $this->ohysBlacklistTitleRepository = Container::getInstance()->make(OhysBlacklistTitleRepository::class);
    }

    public function isBlacklistedTitle(string $title): string
    {
        return $this->ohysBlacklistTitleRepository->Contains(strtolower($title));
    }
}
