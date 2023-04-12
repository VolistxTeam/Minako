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

        $buildResponse = $searchQuery->getCollection()->transform(function ($item) {
            $filteredMappingData = [['service' => 'notify/character', 'service_id' => (string) $item->notifyID]];

            if (is_array($item->mappings)) {
                foreach ($item->mappings as $item2) {
                    $filteredMappingData[] = ['service' => $item2['service'], 'service_id' => $item2['serviceId']];
                }
            }

            return [
                'id'    => $item->uniqueID,
                'names' => [
                    'canonical' => $item->name_canonical,
                    'english'   => $item->name_english,
                    'japanese'  => $item->name_japanese,
                    'synonyms'  => $item->name_synonyms,
                ],
                'description' => $item->description,
                'image'       => [
                    'width'  => $item->image_width,
                    'height' => $item->image_height,
                    'format' => 'jpg',
                    'link'   => config('app.url', 'http://localhost').'/character/'.$item->uniqueID.'/image',
                ],
                'attributes' => $item->attributes,
                'mappings'   => $filteredMappingData,
                'created_at' => (string) $item->created_at,
                'updated_at' => (string) $item->updated_at,
            ];
        });

        return response()->json($buildResponse);
    }

    public function GetImage($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Key not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $id = $itemQuery->uniqueID;
        $imagePath = 'characters/'.$id.'.jpg';

        if (!Storage::disk('local')->exists($imagePath)) {
            return response('Key not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $contents = Storage::disk('local')->get($imagePath);

        return Response::make($contents, 200)->header('Content-Type', 'image/jpeg');
    }

    public function GetCharacter($id)
    {
        $itemQuery = NotifyCharacter::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Character not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $filteredMappingData = [['service' => 'notify/character', 'service_id' => (string) $itemQuery->notifyID]];

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
                'link'   => config('app.url', 'http://localhost').'/character/'.$itemQuery->uniqueID.'/image',
            ],
            'attributes' => $itemQuery->attributes,
            'mappings'   => $filteredMappingData,
            'created_at' => (string) $itemQuery->created_at,
            'updated_at' => (string) $itemQuery->updated_at,
        ];

        return response()->json($buildResponse);
    }
}
