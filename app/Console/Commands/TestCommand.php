<?php

namespace App\Console\Commands;

use App\Classes\AnimeSearch;
use App\Models\OhysRelation;
use App\Models\OhysTorrent;
use Carbon\Carbon;
use DateTime;
use Illuminate\Console\Command;
use Lib16\RSS\Channel;
use Lib16\RSS\RssMarkup;

class TestCommand extends Command
{
    protected $signature = 'test';

    protected $description = '';

    public function handle()
    {
        $torrentQuery = OhysTorrent::query()->latest()->orderBy('info_createdDate', 'DESC')->limit(25)->get()->toArray();

        $channel = Channel::create(
            'Anime Database',
            'Anime Database RSS',
            'https://cryental.dev/services/anime'
        );

        foreach ($torrentQuery as $torrentItem) {
            $itemDescription = !empty($torrentItem['episode']) ? " - Episode {$torrentItem['episode']}" : '';
            $channel
                ->item(
                    $torrentItem['torrentName'],
                    "{$torrentItem['title']}{$itemDescription}",
                    "https://api.minako.moe/ohys/{$torrentItem['uniqueID']}/download?type=torrent"
                )
                ->pubDate(Carbon::createFromTimeString($torrentItem['info_createdDate'], 'Asia/Tokyo')->toDateTime());
        }

        $rssString = (string) $channel;
        var_export($rssString);
        return 0;
    }
}
