<?php

/** @var Router $router */

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

use Laravel\Lumen\Routing\Router;

$router->group(['prefix' => 'cache'], function () use ($router) {
    $router->get('/flush', function () {
        \Illuminate\Support\Facades\Cache::flush();
        return response()->json(['message' => 'All caches are flushed.']);
    });
});

$router->group(['prefix' => 'anime'], function () use ($router) {
    $router->get('/{uniqueID}/sync', 'Services\AnimeController@SyncEpisodes');
});

$router->group(['middleware' => ['cacheResponse']], function () use ($router) {
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
        $router->get('/{uniqueID}/relations', 'Services\AnimeController@GetRelations');
        $router->get('/search/{name}', 'Services\AnimeController@Search');
    });

    $router->group(['prefix' => 'episode'], function () use ($router) {
        $router->get('/{id}', 'Services\EpisodeController@GetEpisode');
        $router->get('/search/{name}', 'Services\EpisodeController@Search');
    });

    $router->group(['prefix' => 'company'], function () use ($router) {
        $router->get('/{id}', 'Services\CompanyController@GetCompany');
        $router->get('/search/{name}', 'Services\CompanyController@Search');
    });

    $router->group(['prefix' => 'character'], function () use ($router) {
        $router->get('/{id}', 'Services\CharacterController@GetCharacter');
        $router->get('/{id}/image', 'Services\CharacterController@GetImage');
        $router->get('/search/{name}', 'Services\CharacterController@Search');
    });

    $router->group(['prefix' => 'ohys'], function () use ($router) {
        $router->get('/', 'Services\OhysController@GetRecentTorrents');
        $router->get('/{id}', 'Services\OhysController@GetTorrent');
        $router->get('/{id}/download/torrent', 'Services\OhysController@DownloadTorrent');
        $router->get('/{id}/download/magnet', 'Services\OhysController@DownloadMagnet');
        $router->get('/search/{name}', 'Services\OhysController@Search');
        $router->get('/service/rss', 'Services\OhysController@GetRSS');
    });
});

$router->group(['prefix' => 'blacklist'], function () use ($router) {
    $router->get('/', 'Auth\OhysBlacklistController@GetOhysBlacklistTitles');
    $router->post('/', 'Auth\OhysBlacklistController@CreateOhysBlacklistTitle');
    $router->get('/{title_id}', 'Auth\OhysBlacklistController@GetOhysBlacklistTitle');
    $router->delete('/{title_id}', 'Auth\OhysBlacklistController@DeleteOhysBlacklistTitle');
    $router->patch('/{title_id}', 'Auth\OhysBlacklistController@UpdateOhysBlacklistTitle');
});