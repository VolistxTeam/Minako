<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Company;
use App\Models\NotifyCompany;

class CompanyController extends Controller
{
    public function Search($name)
    {
        $name = urldecode($name);
        $name = $this->escapeElasticReservedChars($name);

        $searchQuery = NotifyCompany::searchByName($name, 50);
        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = Company::fromModel($query->obj)->GetDTO();
        }

        return response()->json($response);
    }

    public function GetCompany(string $id)
    {
        $itemQuery = NotifyCompany::query()->where('uniqueID', $id)->first();

        if (!$itemQuery) {
            return response('Company not found: '.$id, 404)->header('Content-Type', 'text/plain');
        }

        $response = Company::fromModel($itemQuery)->GetDTO();

        return response()->json($response);
    }
}
