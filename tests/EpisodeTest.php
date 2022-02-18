<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

class EpisodeTest extends BaseTestCase
{
    public function createApplication(): \Laravel\Lumen\Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function testEpisode()
    {
        $searchResult = $this->get("episode/search/It's Cruel! It's Mysterious! It's My Destiny!")->seeStatusCode(200)->response->decodeResponseJson();

        $episodeID = $searchResult[0]['id'];

        $this->get('episode/'.$episodeID)->seeStatusCode(200);
    }
}
