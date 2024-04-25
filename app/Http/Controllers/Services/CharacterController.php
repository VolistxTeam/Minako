<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Character;
use App\Models\NotifyCharacter;
use App\Repositories\AnimeRepository;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class CharacterController extends Controller
{
    private AnimeRepository $animeRepository;

    public function __construct(AnimeRepository $animeRepository)
    {
        $this->animeRepository = $animeRepository;
    }

    public function Search($name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = $this->animeRepository->searchNotifyCharacterByName($name, 50);
        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = Character::fromModel($query->obj)->GetDTO();
        }

        return response()->json($response);
    }

    public function GetImage($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Key not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $id = $itemQuery->uniqueID;
        $imagePath = 'characters/' . $id . '.jpg';

        if (!Storage::disk('local')->exists($imagePath)) {
            return response('Key not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $contents = Storage::disk('local')->get($imagePath);

        return Response::make($contents, 200)->header('Content-Type', 'image/jpeg');
    }

    public function GetCharacter($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Character not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $response = Character::fromModel($itemQuery)->GetDTO();

        return response()->json($response);
    }
}
