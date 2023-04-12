<?php

namespace App\Http\Controllers\Services;

use App\Models\NotifyCompany;

class CompanyController extends Controller
{
    public function Search(string $name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = NotifyCompany::query()
            ->where('name_english', 'LIKE', "%$name%")
            ->orWhere('name_japanese', 'LIKE', "%$name%")
            ->orWhereJsonContains('name_synonyms', $name)
            ->take(100)
            ->paginate(50, ['*'], 'page', 1);

        $buildResponse = $searchQuery->getCollection()->map(function ($item) {
            return [
                'id'    => $item->uniqueID,
                'names' => [
                    'english'  => $item->name_english,
                    'japanese' => $item->name_japanese,
                    'synonyms' => $item->name_synonyms,
                ],
                'description' => $item->description,
                'email'       => $item->email,
                'links'       => $item->links,
                'created_at'  => (string) $item->created_at,
                'updated_at'  => (string) $item->updated_at,
            ];
        });

        return response()->json($buildResponse);
    }

    public function GetCompany(string $id)
    {
        $itemQuery = NotifyCompany::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Company not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $buildResponse = [
            'id'    => $itemQuery->uniqueID,
            'names' => [
                'english'  => $itemQuery->name_english,
                'japanese' => $itemQuery->name_japanese,
                'synonyms' => $itemQuery->name_synonyms,
            ],
            'description' => $itemQuery->description,
            'email'       => $itemQuery->email,
            'links'       => $itemQuery->links,
            'created_at'  => (string) $itemQuery->created_at,
            'updated_at'  => (string) $itemQuery->updated_at,
        ];

        return response()->json($buildResponse);
    }
}
