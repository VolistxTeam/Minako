<?php

use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class CharacterTest extends BaseTestCase
{
    public function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function testCharacter()
    {
        $searchResult = $this->get("character/search/Asuka Kurashina")->seeStatusCode(200)->response->decodeResponseJson();

        $characterID = $searchResult[0]['id'];

        $this->get('character/' . $characterID)->seeStatusCode(200);
    }
}