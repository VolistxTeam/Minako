<?php

use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class OhysTest extends BaseTestCase
{
    public function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function testOhys()
    {
        $searchResult = $this->get("ohys")->seeStatusCode(200)->response->decodeResponseJson();

        $ohysID = $searchResult['items'][0]['id'];

        $this->get('ohys/' . $ohysID)->seeStatusCode(200);
    }
}