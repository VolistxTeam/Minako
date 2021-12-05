<?php

namespace App\Http\Controllers\Services;

use App\Models\OhysTorrent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class OhysController extends Controller
{
    public function Search(Request $request, $name)
    {
        $name = urldecode($name);

        $torrentQuery = OhysTorrent::search($this->escapeElasticReservedChars($name))->paginate(50, 'p');

        $itemsFiltered = [];

        foreach ($torrentQuery->items() as $torrent) {
            $announcesRebuild = [];

            foreach ($torrent['info_torrent_announces'] as $item) {
                if (count($item) > 0) {
                    $announcesRebuild[] = $item[0];
                }
            }

            $itemsFiltered[] = [
                'id' => $torrent['uniqueID'],
                'release_group' => $torrent['releaseGroup'],
                'broadcaster' => $torrent['broadcaster'],
                'title' => $torrent['title'],
                'episode' => $torrent['episode'],
                'torrent_name' => $torrent['torrentName'],
                'info' => [
                    'size' => $torrent['info_totalSize'],
                    'created_at' => $torrent['info_createdDate'],
                ],
                'metadata' => [
                    'video' => [
                        'codec' => $torrent['metadata_video_codec'],
                        'resolution' => $torrent['metadata_video_resolution']
                    ],
                    'audio' => [
                        'codec' => $torrent['metadata_audio_codec']
                    ]
                ]
            ];
        }

        $buildResponse = [
            'pagination' => [
                'per_page' => $torrentQuery->perPage(),
                'current' => $torrentQuery->currentPage(),
                'total' => $torrentQuery->lastPage(),
            ],
            'items' => $itemsFiltered
        ];

        return response()->json($buildResponse);
    }

    public function GetRecentTorrents()
    {
        $torrentQuery = OhysTorrent::query()->orderBy('info_createdDate', 'DESC')->paginate(50, ['*'], 'p');

        $itemsFiltered = [];

        foreach ($torrentQuery->items() as $torrent) {
            $announcesRebuild = [];

            foreach ($torrent['info_torrent_announces'] as $item) {
                if (count($item) > 0) {
                    $announcesRebuild[] = $item[0];
                }
            }

            $itemsFiltered[] = [
                'id' => $torrent['uniqueID'],
                'release_group' => $torrent['releaseGroup'],
                'broadcaster' => $torrent['broadcaster'],
                'title' => $torrent['title'],
                'episode' => $torrent['episode'],
                'torrent_name' => $torrent['torrentName'],
                'info' => [
                    'size' => $torrent['info_totalSize'],
                    'created_at' => $torrent['info_createdDate'],
                ],
                'metadata' => [
                    'video' => [
                        'codec' => $torrent['metadata_video_codec'],
                        'resolution' => $torrent['metadata_video_resolution']
                    ],
                    'audio' => [
                        'codec' => $torrent['metadata_audio_codec']
                    ]
                ]
            ];
        }

        $buildResponse = [
            'pagination' => [
                'per_page' => $torrentQuery->perPage(),
                'current' => $torrentQuery->currentPage(),
                'total' => $torrentQuery->lastPage(),
            ],
            'items' => $itemsFiltered
        ];

        return response()->json($buildResponse);
    }

    public function GetTorrent($id)
    {
        $torrentQuery = OhysTorrent::query()->where('uniqueID', $id)->first();

        if (empty($torrentQuery)) {
            return response('Torrent not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $announcesRebuild = [];

        foreach ($torrentQuery['info_torrent_announces'] as $item) {
            if (count($item) > 0) {
                $announcesRebuild[] = $item[0];
            }
        }

        $buildResponse = [
            'id' => $torrentQuery['uniqueID'],
            'anime_id' => !empty($torrentQuery->anime->uniqueID) ? $torrentQuery->anime->uniqueID : null,
            'release_group' => $torrentQuery['releaseGroup'],
            'broadcaster' => $torrentQuery['broadcaster'],
            'title' => $torrentQuery['title'],
            'episode' => $torrentQuery['episode'],
            'torrent_name' => $torrentQuery['torrentName'],
            'info' => [
                'hash' => $torrentQuery['info_totalHash'],
                'size' => $torrentQuery['info_totalSize'],
                'created_at' => $torrentQuery['info_createdDate'],
                'announces' => $announcesRebuild,
                'files' => $torrentQuery['info_torrent_files'],
            ],
            'metadata' => [
                'video' => [
                    'codec' => $torrentQuery['metadata_video_codec'],
                    'resolution' => $torrentQuery['metadata_video_resolution']
                ],
                'audio' => [
                    'codec' => $torrentQuery['metadata_audio_codec']
                ]
            ],
            'download' => [
                'official' => [
                    'torrent' => 'https://ohys.nl/tt/disk/' . $torrentQuery['torrentName']
                ],
                'mirror' => [
                    'torrent' => env('APP_URL', 'http://localhost') . '/ohys/' . $torrentQuery['uniqueID'] . '/download?type=torrent',
                    'magnet' => env('APP_URL', 'http://localhost') . '/ohys/' . $torrentQuery['uniqueID'] . '/download?type=magnet',
                ]
            ]
        ];

        return response()->json($buildResponse);
    }

    public function DownloadTorrent(Request $request, $id)
    {
        $torrentQuery = OhysTorrent::query()->where('uniqueID', $id)->first();

        if (empty($torrentQuery)) {
            return response('Item not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $requestType = $request->input('type', 'magnet');

        if ($requestType == 'torrent') {
            $contents = Storage::disk('local')->get('torrents/' . $torrentQuery->torrentName);

            if (empty($contents)) {
                return response('Torrent file not found: ' . $id, 404)->header('Content-Type', 'text/plain');
            }

            return Response::make($contents, 200)->header("Content-Type", "application/x-bittorrent")->header('Content-disposition', 'attachment; filename=' . $torrentQuery->uniqueID . '.torrent');
        } else {
            $redirect = new RedirectResponse($torrentQuery->hidden_download_magnet, 302, []);

            $redirect->setRequest($request);

            return $redirect;
        }
    }
}
