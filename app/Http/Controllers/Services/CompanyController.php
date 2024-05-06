<?php

namespace App\Http\Controllers\Services;

use App\DataTransferObjects\Company;
use App\Repositories\AnimeRepository;

class CompanyController extends Controller
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

        $searchQuery = $this->animeRepository->searchCompanyByName($name, 50);
        $response = [];

        foreach ($searchQuery as $query) {
            $response[] = Company::fromModel($query->obj)->GetDTO();
        }

        return response()->json($response);
    }

    public function GetCompany(string $id)
    {
        $company = $this->animeRepository->getNotifyCompanyByUniqueId($id);

        if (!$company) {
            return response('Company not found: ' . $id, 404)->header('Content-Type', 'text/plain');
        }

        $response = Company::fromModel($company)->GetDTO();

        return response()->json($response);
    }
}
