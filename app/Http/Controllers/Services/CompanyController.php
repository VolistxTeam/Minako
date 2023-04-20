<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\CompanyDTO;
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

        $response = $searchQuery->getCollection()->map(function ($item) {
            return CompanyDTO::fromModel($item)->GetDTO();
        });

        return response()->json($response);
    }

    public function GetCompany(string $id)
    {
        $itemQuery = NotifyCompany::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Company not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $response = CompanyDTO::fromModel($itemQuery)->GetDTO();

        return response()->json($response);
    }
}
