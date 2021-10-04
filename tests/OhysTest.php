<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class OhysTest extends BaseTestCase
{
    public function createApplication(): \Laravel\Lumen\Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function testOhys()
    {
        $searchResult = $this->get("ohys")->seeStatusCode(200)->response->decodeResponseJson();

        $ohysID = $searchResult['items'][0]['id'];

        $this->get('ohys/' . $ohysID)->seeStatusCode(200);
        $this->get('ohys/' . $ohysID . '/download?type=torrent')->seeStatusCode(200);
    }
}