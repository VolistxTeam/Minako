<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['middleware' => []], function () use ($router) {
    $router->group(['prefix' => 'anime'], function () use ($router) {
        $router->get('/{uniqueID}', 'Services\AnimeController@GetItem');
        $router->get('/{uniqueID}/image', 'Services\AnimeController@GetImage');
        $router->get('/{uniqueID}/episodes/{episodeNumber}', 'Services\AnimeController@GetEpisode');
        $router->get('/{uniqueID}/episodes', 'Services\AnimeController@GetEpisodes');
        $router->get('/{uniqueID}/mappings', 'Services\AnimeController@GetMappings');
        $router->get('/{uniqueID}/studios', 'Services\AnimeController@GetStudios');
        $router->get('/{uniqueID}/producers', 'Services\AnimeController@GetProducers');
        $router->get('/{uniqueID}/licensors', 'Services\AnimeController@GetLicensors');
        $router->get('/{uniqueID}/characters', 'Services\AnimeController@GetCharacters');
        $router->get('/search/{name}', 'Services\AnimeController@Search');
    });
});