<?php

namespace App\Http\Controllers\Services;

use App\Models\NotifyCharacter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class CharacterController extends Controller
{
    public function Search($name)
    {
        $searchQuery = NotifyCharacter::search($this->escapeElasticReservedChars($name))->paginate(50, 'page', 1);

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = array();
            $newArray['id'] = $item['uniqueID'];
            $newArray['name'] = $item['name_canonical'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetImage($uniqueID)
    {
        $actualPath = storage_path('app/minako/characters/' . $uniqueID . '.jpg');

        ray($actualPath);
        if (!file_exists($actualPath)) {
            return response('Image not found: ' . $uniqueID, 404)->header('Content-Type', 'text/plain');
        }

        $file = File::get($actualPath);
        $type = File::mimeType($actualPath);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function GetCharacter($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (empty($itemQuery)) {
            return response('Character not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [];

        array_push($filteredMappingData, ['service' => 'notify/character', 'service_id' => (string)$itemQuery->notifyID]);

        foreach ($itemQuery->mappings as $item) {
            array_push($filteredMappingData, ['service' => $item['service'], 'service_id' => $item['serviceId']]);
        }

        $buildResponse = [
            'id' => $itemQuery->uniqueID,
            'names' => [
                'canonical' => $itemQuery->name_canonical,
                'english' => $itemQuery->name_english,
                'japanese' => $itemQuery->name_japanese,
                'synonyms' => $itemQuery->name_synonyms
            ],
            'description' => $itemQuery->description,
            'image' => [
                'width' => $itemQuery->image_width,
                'height' => $itemQuery->image_height,
                'format' => 'jpg',
                'link' => ''
            ],
            'attributes' => $itemQuery->attributes,
            'mappings' => $filteredMappingData,
            'created_at' => (string)$itemQuery->created_at,
            'updated_at' => (string)$itemQuery->updated_at
        ];

        return response()->json($buildResponse);
    }
}
