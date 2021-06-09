<?php

namespace App\Http\Controllers\Services;

use App\Models\NotifyCompany;

class CompanyController extends Controller
{
    public function Search($name)
    {
        $name = urldecode($name);

        $searchQuery = NotifyCompany::search($this->escapeElasticReservedChars($name))->paginate(50, 'page', 1);

        $buildResponse = [];

        foreach ($searchQuery->items() as $item) {
            $newArray = array();
            $newArray['id'] = $item['uniqueID'];
            $newArray['title'] = $item['name_english'];

            $buildResponse[] = $newArray;
        }

        return response()->json($buildResponse);
    }

    public function GetCompany($id)
    {
        $itemQuery = NotifyCompany::query()->where('uniqueID', $id)->first();

        if (empty($itemQuery)) {
            return response('Company not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $buildResponse = [
            'id' => $itemQuery->uniqueID,
            'names' => [
                'english' => $itemQuery->name_english,
                'japanese' => $itemQuery->name_japanese,
                'synonyms' => $itemQuery->name_synonyms
            ],
            'description' => $itemQuery->description,
            'email' => $itemQuery->email,
            'links' => $itemQuery->links,
            'created_at' => (string)$itemQuery->created_at,
            'updated_at' => (string)$itemQuery->updated_at
        ];

        return response()->json($buildResponse);
    }
}
