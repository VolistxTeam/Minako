<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class CharacterTest extends BaseTestCase
{
    public function createApplication(): \Laravel\Lumen\Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function testCharacter()
    {
        $searchResult = $this->get('character/search/Asuka Kurashina')->seeStatusCode(200)->response->decodeResponseJson();

        $characterID = $searchResult[0]['id'];

        $this->get('character/'.$characterID)->seeStatusCode(200);
        $this->get('character/'.$characterID.'/image')->seeStatusCode(200);
    }
}
