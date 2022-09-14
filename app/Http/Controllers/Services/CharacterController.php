<?php

namespace App\Http\Controllers\Services;

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

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = [];
            $newArray['id'] = $item['uniqueID'];
            $newArray['name'] = $item['name_canonical'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetImage($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (empty($itemQuery)) {
            return response('Key not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $id = $itemQuery->uniqueID;

        $contents = Storage::disk('local')->get('characters/'.$id.'.jpg');

        if (empty($contents)) {
            return response('Key not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        return Response::make($contents, 200)->header('Content-Type', 'image/jpeg');
    }

    public function GetCharacter($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (empty($itemQuery)) {
            return response('Character not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [];

        $filteredMappingData[] = ['service' => 'notify/character', 'service_id' => (string) $itemQuery->notifyID];

        if (is_array($itemQuery->mappings)) {
            foreach ($itemQuery->mappings as $item) {
                $filteredMappingData[] = ['service' => $item['service'], 'service_id' => $item['serviceId']];
            }
        }

        $buildResponse = [
            'id'    => $itemQuery->uniqueID,
            'names' => [
                'canonical' => $itemQuery->name_canonical,
                'english'   => $itemQuery->name_english,
                'japanese'  => $itemQuery->name_japanese,
                'synonyms'  => $itemQuery->name_synonyms,
            ],
            'description' => $itemQuery->description,
            'image'       => [
                'width'  => $itemQuery->image_width,
                'height' => $itemQuery->image_height,
                'format' => 'jpg',
                'link'   => config('APP_URL', 'http://localhost').'/character/'.$itemQuery->uniqueID.'/image',
            ],
            'attributes' => $itemQuery->attributes,
            'mappings'   => $filteredMappingData,
            'created_at' => (string) $itemQuery->created_at,
            'updated_at' => (string) $itemQuery->updated_at,
        ];

        return response()->json($buildResponse);
    }
}
