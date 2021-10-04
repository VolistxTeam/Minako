<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class CompanyTest extends BaseTestCase
{
    public function createApplication(): \Laravel\Lumen\Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function testCompany()
    {
        $searchResult = $this->get("company/search/TV Tokyo")->seeStatusCode(200)->response->decodeResponseJson();

        $companyID = $searchResult[0]['id'];

        $this->get('company/' . $companyID)->seeStatusCode(200);
    }
}