<?php

namespace App\Http\Controllers\Services;

use App\Models\OhysTorrent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class OhysController extends Controller
{
    public function Search($name)
    {
        $name = urldecode($name);

        $searchQuery = OhysTorrent::search($this->escapeElasticReservedChars($name))->paginate(50, 'page', 1);

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = array();
            $newArray['id'] = $item['uniqueID'];
            $newArray['release_group'] = $item['releaseGroup'];
            $newArray['torrent_name'] = $item['torrentName'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetRecentTorrents()
    {
        $torrentQuery = OhysTorrent::query()->latest()->orderBy('info_createdDate', 'DESC')->paginate(50, ['*'], 'p');

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
                'anime_id' => !empty($torrent->anime->uniqueID) ? $torrent->anime->uniqueID : null,
                'release_group' => $torrent['releaseGroup'],
                'title' => $torrent['title'],
                'episode' => $torrent['episode'],
                'torrent_name' => $torrent['torrentName'],
                'info' => [
                    'hash' => $torrent['info_totalHash'],
                    'size' => $torrent['info_totalSize'],
                    'created_at' => $torrent['info_createdDate'],
                    'announces' => $announcesRebuild,
                    'files' => $torrent['info_torrent_files'],
                ],
                'metadata' => [
                    'video' => [
                        'codec' => $torrent['metadata_video_codec'],
                        'resolution' => $torrent['metadata_video_resolution']
                    ],
                    'audio' => [
                        'codec' => $torrent['metadata_audio_codec']
                    ]
                ],
                'download' => [
                    'official' => [
                        'torrent' => 'https://ohys.nl/tt/disk/' . $torrent['torrentName']
                    ],
                    'mirror' => [
                        'torrent' => 'https://api.v2.minako.moe/ohys/download/' . $torrent['uniqueID'] . '?type=torrent',
                        'magnet' => 'https://api.v2.minako.moe/ohys/download/' . $torrentQuery['uniqueID'] . '?type=magnet',
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
        $torrentQuery = OhysTorrent::query()->latest()->where('uniqueID', $id)->first();

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
                    'torrent' => 'https://api.v2.minako.moe/ohys/download/' . $torrentQuery['uniqueID'] . '?type=torrent',
                    'magnet' => 'https://api.v2.minako.moe/ohys/download/' . $torrentQuery['uniqueID'] . '?type=magnet',
                ]
            ]
        ];

        return response()->json($buildResponse);
    }

    public function DownloadTorrent(Request $request, $id)
    {
        $torrentQuery = OhysTorrent::query()->latest()->where('uniqueID', $id)->first();

        if (empty($torrentQuery)) {
            return response('Torrent not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $requestType = $request->input('type', 'magnet');

        if ($requestType == 'torrent') {
            $actualPath = storage_path('app/minako/ohys-torrents/' . $torrentQuery->torrentName);

            if (!file_exists($actualPath)) {
                return response('Torrent file not found: ' . $id, 404)->header('Content-Type', 'text/plain');
            }

            $type = File::mimeType($actualPath);

            return Response::download($actualPath, $id . '.torrent', ["Content-Type" => $type]);
        } else {
            $redirect = new RedirectResponse($torrentQuery->hidden_download_magnet, 302, []);

            $redirect->setRequest($request);

            return $redirect;
        }
    }
}
