<?php

use App\Http\Controllers\Auth\OhysBlacklistController;
use App\Http\Controllers\Services\AnimeController;
use App\Http\Controllers\Services\CharacterController;
use App\Http\Controllers\Services\CompanyController;
use App\Http\Controllers\Services\EpisodeController;
use App\Http\Controllers\Services\OhysController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:2000,1')->group(function () {
    Route::prefix('cache')->group(function () {
        Route::get('/flush', function () {
            Cache::flush();

            return response()->json(['message' => 'All caches are flushed.']);
        });
    });

    Route::prefix('anime')->group(function () {
        Route::get('/{uniqueID}/sync', [AnimeController::class, 'SyncEpisodes']);
    });

    Route::middleware(['CacheResponse'])->group(function () {
        Route::prefix('anime')->group(function () {
            Route::get('/{uniqueID}', [AnimeController::class, 'GetItem']);
            Route::get('/{uniqueID}/image', [AnimeController::class, 'GetImage']);
            Route::get('/{uniqueID}/episodes/{episodeNumber}', [AnimeController::class, 'GetEpisode']);
            Route::get('/{uniqueID}/episodes', [AnimeController::class, 'GetEpisodes']);
            Route::get('/{uniqueID}/mappings', [AnimeController::class, 'GetMappings']);
            Route::get('/{uniqueID}/studios', [AnimeController::class, 'GetStudios']);
            Route::get('/{uniqueID}/producers', [AnimeController::class, 'GetProducers']);
            Route::get('/{uniqueID}/licensors', [AnimeController::class, 'GetLicensors']);
            Route::get('/{uniqueID}/characters', [AnimeController::class, 'GetCharacters']);
            Route::get('/{uniqueID}/relations', [AnimeController::class, 'GetRelations']);
            Route::get('/{uniqueID}/torrents', [AnimeController::class, 'GetTorrents']);
            Route::get('/search/{name}', [AnimeController::class, 'Search']);
        });

        Route::prefix('episode')->group(function () {
            Route::get('/{id}', [EpisodeController::class, 'GetEpisode']);
            Route::get('/search/{name}', [EpisodeController::class, 'Search']);
        });

        Route::prefix('company')->group(function () {
            Route::get('/{id}', [CompanyController::class, 'GetCompany']);
            Route::get('/search/{name}', [CompanyController::class, 'Search']);
        });

        Route::prefix('character')->group(function () {
            Route::get('/{id}', [CharacterController::class, 'GetCharacter']);
            Route::get('/{id}/image', [CharacterController::class, 'GetImage']);
            Route::get('/search/{name}', [CharacterController::class, 'Search']);
        });

        Route::prefix('ohys')->group(function () {
            Route::get('/', [OhysController::class, 'GetRecentTorrents']);
            Route::get('/{id}', [OhysController::class, 'GetTorrent']);
            Route::get('/{id}/download', [OhysController::class, 'DownloadTorrent']);
            Route::get('/search/{name}', [OhysController::class, 'Search']);
            Route::get('/service/rss', [OhysController::class, 'GetRSS']);
        });
    });

    Route::prefix('blacklist')->group(function () {
        Route::get('/', [OhysBlacklistController::class, 'Get']);
        Route::post('/', [OhysBlacklistController::class, 'Create']);
        Route::get('/{title_id}', [OhysBlacklistController::class, 'GetFromID']);
        Route::delete('/{title_id}', [OhysBlacklistController::class, 'Delete']);
        Route::patch('/{title_id}', [OhysBlacklistController::class, 'Update']);
    });
});
