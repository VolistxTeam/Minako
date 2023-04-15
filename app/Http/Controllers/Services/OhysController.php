<?php

namespace App\Http\Controllers\Services;

use App\Facades\OhysBlacklist;
use App\Models\OhysTorrent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

class OhysController extends Controller
{
    private function formatTorrentData($torrent)
    {
        $announcesRebuild = collect($torrent['info_torrent_announces'])->filter(function ($item) {
            return count($item) > 0;
        })->pluck(0)->toArray();

        $appUrl = config('app.url', 'http://localhost');

        return [
            'id'            => $torrent['uniqueID'],
            'anime_id'      => optional($torrent->anime)->uniqueID,
            'release_group' => $torrent['releaseGroup'],
            'broadcaster'   => $torrent['broadcaster'],
            'title'         => $torrent['title'],
            'episode'       => $torrent['episode'],
            'torrent_name'  => $torrent['torrentName'],
            'info'          => [
                'hash'       => $torrent['info_totalHash'],
                'size'       => $torrent['info_totalSize'],
                'created_at' => $torrent['info_createdDate'],
                'announces'  => $announcesRebuild,
                'files'      => $torrent['info_torrent_files'],
            ],
            'metadata' => [
                'video' => [
                    'codec'      => $torrent['metadata_video_codec'],
                    'resolution' => $torrent['metadata_video_resolution'],
                ],
                'audio' => [
                    'codec' => $torrent['metadata_audio_codec'],
                ],
            ],
            'download' => [
                'official' => [
                    'torrent' => 'https://ohys.nl/tt/disk/'.$torrent['torrentName'],
                ],
                'mirror' => [
                    'torrent' => $appUrl.'/ohys/'.$torrent['uniqueID'].'/download?type=torrent',
                    'magnet'  => $appUrl.'/ohys/'.$torrent['uniqueID'].'/download?type=magnet',
                ],
            ],
        ];
    }

    public function Search(Request $request, $name)
    {
        $name = urldecode($name);

        $torrentQuery = OhysTorrent::query()
            ->where('title', 'LIKE', "%$name%")
            ->orWhere('torrentName', 'LIKE', "%$name%")
            ->paginate(50, ['*'], 'p');

        $itemsFiltered = $torrentQuery->getCollection()->map(function ($torrent) {
            return $this->formatTorrentData($torrent);
        })->filter(function ($torrent) {
            return OhysBlacklist::isBlacklistedTitle($torrent['title']);
        });

        $buildResponse = [
            'pagination' => [
                'per_page' => $torrentQuery->perPage(),
                'current'  => $torrentQuery->currentPage(),
                'total'    => $torrentQuery->lastPage(),
            ],
            'items' => $itemsFiltered->values(),
        ];

        return response()->json($buildResponse);
    }

    public function GetRSS()
    {
        $torrentQuery = OhysTorrent::query()
            ->orderBy('info_createdDate', 'DESC')
            ->limit(100)
            ->get()
            ->filter(function ($torrent) {
                return OhysBlacklist::isBlacklistedTitle($torrent->title);
            })
            ->toArray();

        $feed = new Feed();
        $channel = new Channel();
        $channel
            ->title('Anime Database')
            ->description('Anime Database RSS Service')
            ->url('https://cryental.dev/services/anime')
            ->feedUrl(config('app.url', 'http://localhost').'/ohys/service/rss')
            ->appendTo($feed);

        foreach ($torrentQuery as $torrentItem) {
            $itemDescription = !empty($torrentItem['episode']) ? " - Episode {$torrentItem['episode']}" : '';
            $item = new Item();
            $item
                ->title("{$torrentItem['title']}{$itemDescription}")
                ->url(config('app.url', 'http://localhost')."/ohys/{$torrentItem['uniqueID']}/download?type=torrent")
                ->pubDate(Carbon::createFromTimeString($torrentItem['info_createdDate'], 'Asia/Tokyo')->getTimestamp())
                ->appendTo($channel);
        }
        $rssString = $feed->render();

        return response($rssString)->withHeaders(['Content-Type' => 'application/rss+xml; charset=utf-8']);
    }

    public function GetRecentTorrents()
    {
        $torrentQuery = OhysTorrent::query()->orderBy('info_createdDate', 'DESC')->paginate(50, ['*'], 'p');

        $itemsFiltered = $torrentQuery->getCollection()->map(function ($torrent) {
            return $this->formatTorrentData($torrent);
        });

        $buildResponse = [
            'pagination' => [
                'per_page' => $torrentQuery->perPage(),
                'current'  => $torrentQuery->currentPage(),
                'total'    => $torrentQuery->lastPage(),
            ],
            'items' => $itemsFiltered,
        ];

        return response()->json($buildResponse);
    }

    public function GetTorrent($id)
    {
        $torrentQuery = OhysTorrent::query()->where('uniqueID', $id)->first();

        if (empty($torrentQuery)) {
            return response('Torrent not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $buildResponse = $this->formatTorrentData($torrentQuery);

        return response()->json($buildResponse);
    }

    public function DownloadTorrent(Request $request, $id)
    {
        $torrentQuery = OhysTorrent::query()
            ->where('uniqueID', $id)
            ->first();

        if (empty($torrentQuery) || OhysBlacklist::isBlacklistedTitle($torrentQuery->title)) {
            return response('Item not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $requestType = $request->input('type', 'magnet');

        if ($requestType == 'torrent') {
            $contents = Storage::disk('local')->get('torrents/'.$torrentQuery->torrentName);

            if (empty($contents)) {
                return response('Torrent file not found: '.$id, 404)->header('Content-Type', 'text/plain');
            }

            return Response::make($contents, 200)->header('Content-Type', 'application/x-bittorrent')->header('Content-disposition', 'attachment; filename='.$torrentQuery->uniqueID.'.torrent');
        } else {
            return redirect()->to($torrentQuery->hidden_download_magnet);
        }
    }
}
