<?php

use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class AnimeTest extends BaseTestCase
{
    public function createApplication(): Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function testAnime()
    {
        $searchResult = $this->get("anime/search/ao no kanata")->seeStatusCode(200)->response->decodeResponseJson();

        $animeID = $searchResult[0]['id'];

        $this->get('anime/' . $animeID)->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/episodes')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/mappings')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/studios')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/producers')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/licensors')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/characters')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/relations')->seeStatusCode(200);
        $this->get('anime/' . $animeID . '/image')->seeStatusCode(200);
    }
}