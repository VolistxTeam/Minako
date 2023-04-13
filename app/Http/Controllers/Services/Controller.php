<?php

namespace App\Http\Controllers\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function escapeElasticReservedChars(string $string): string
    {
        $regex = '/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/';

        return preg_replace($regex, addslashes('\\$0'), $string);
    }

    protected function customPaginate($items, int $perPage = 15, ?int $page = null, array $options = []): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage() ?: 1;

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    protected function checkTitleBlacklist($title) {
        if (filesize(__DIR__ . "/../../../../resources/blacklist.txt") == 0) {
            return false;
        }

        $blacklist = file_get_contents(__DIR__ . "/../../../../resources/blacklist.txt");
        $blacklistArr = explode("\n", $blacklist);
        $lowercaseTitle = strtolower($title);
        foreach ($blacklistArr as $blacklistedTitle) {
            if (str_contains($lowercaseTitle, strtolower($blacklistedTitle))) {
                return true;
            }
        }
        return false;
    }
}
