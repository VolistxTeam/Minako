<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Torrent;
use App\Facades\OhysBlacklist;
use App\Repositories\AnimeRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

class OhysController extends Controller
{
    private AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        $this->animeRepository = $animeRepository;
    }

    public function Search(Request $request, $name)
    {
        $name = urldecode($name);

        $torrentQuery = $this->animeRepository->searchOhysTorrentsByName($name);

        $itemsFiltered = $torrentQuery->getCollection()->filter(function ($torrent) {
            return ! OhysBlacklist::isBlacklistedTitle($torrent['title']);
        })->map(function ($torrent) {
            return Torrent::fromModel($torrent)->GetDTO();
        });

        $response = [
            'pagination' => [
                'per_page' => $torrentQuery->perPage(),
                'current' => $torrentQuery->currentPage(),
                'total' => $torrentQuery->lastPage(),
            ],
            'items' => $itemsFiltered->values(),
        ];

        return response()->json($response);
    }

    public function GetRSS()
    {
        $torrentQuery = $this->animeRepository->getOhysTorrentsRSS()->toArray();

        $feed = new Feed();
        $channel = new Channel();
        $channel
            ->title('Anime Database')
            ->description('Anime Database RSS Service')
            ->url('https://cryental.dev/services/anime')
            ->feedUrl(config('app.url', 'http://localhost').'/ohys/service/rss')
            ->appendTo($feed);

        foreach ($torrentQuery as $torrentItem) {
            $item = new Item();
            $item
                ->title("{$torrentItem['torrentName']}")
                ->url(config('app.url', 'http://localhost')."/ohys/{$torrentItem['uniqueID']}/download?type=torrent")
                ->pubDate(Carbon::createFromTimeString($torrentItem['info_createdDate'], 'Asia/Tokyo')->getTimestamp())
                ->appendTo($channel);
        }
        $rssString = $feed->render();

        return response($rssString)->withHeaders(['Content-Type' => 'application/rss+xml; charset=utf-8']);
    }

    public function GetRecentTorrents()
    {
        $recentTorrents = $this->animeRepository->getRecentOhysTorrents();

        $itemsFiltered = $recentTorrents->getCollection()->filter(function ($torrent) {
            return ! OhysBlacklist::isBlacklistedTitle($torrent['title']);
        })->map(function ($torrent) {
            return Torrent::fromModel($torrent)->GetDTO();
        });

        $response = [
            'pagination' => [
                'per_page' => $recentTorrents->perPage(),
                'current' => $recentTorrents->currentPage(),
                'total' => $recentTorrents->lastPage(),
            ],
            'items' => $itemsFiltered->values(),
        ];

        return response()->json($response);
    }

    public function GetTorrent($id)
    {
        $torrent = $this->animeRepository->getOhysTorrentByUniqueID($id);

        if (empty($torrent) || OhysBlacklist::isBlacklistedTitle($torrent->title)) {
            return response('Item not found: '.$id)->header('Content-Type', 'text/plain');
        }

        $response = Torrent::fromModel($torrent)->GetDTO();

        return response()->json($response);
    }

    public function DownloadTorrent(Request $request, $id)
    {
        $torrent = $this->animeRepository->getOhysTorrentByUniqueID($id);

        if (empty($torrent) || OhysBlacklist::isBlacklistedTitle($torrent->title)) {
            return response('Item not found: '.$id)->header('Content-Type', 'text/plain');
        }

        $requestType = $request->input('type', 'magnet');

        if ($requestType == 'torrent') {
            $contents = Storage::disk('local')->get('torrents/'.$torrent->torrentName);

            if (empty($contents)) {
                return response('Torrent file not found: '.$id)->header('Content-Type', 'text/plain');
            }

            return Response::make($contents)->header('Content-Type', 'application/x-bittorrent')->header('Content-disposition', 'attachment; filename='.$torrent->uniqueID.'.torrent');
        } else {
            return response()->noContent(302)->withHeaders(['Location' => $torrent->hidden_download_magnet]);
        }
    }
}
