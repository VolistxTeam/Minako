<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\CharacterDTO;
use App\Models\NotifyCharacter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class CharacterController extends Controller
{
    public function Search($name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = NotifyCharacter::query()
            ->where('name_canonical', 'LIKE', "%$name%")
            ->orWhere('name_english', 'LIKE', "%$name%")
            ->orWhere('name_japanese', 'LIKE', "%$name%")
            ->orWhereJsonContains('name_synonyms', $name)
            ->take(100)
            ->paginate(50, ['*'], 'page', 1);

        $response = $searchQuery->getCollection()->transform(function ($item) {
            return CharacterDTO::fromModel($item)->GetDTO();
        });

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

        $response =  CharacterDTO::fromModel($itemQuery)->GetDTO();

        return response()->json($response);
    }
}
