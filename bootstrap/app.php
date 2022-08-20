<?php

use LumenRateLimiting\ThrottleRequests;

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->register(Chuckrincon\LumenConfigDiscover\DiscoverServiceProvider::class);

$app->withFacades();
$app->withEloquent();

// Packages to provide compatibility with Laravel and Redis support
$app->register(Illuminate\Redis\RedisServiceProvider::class);

$app->register(Irazasyed\Larasupport\Providers\ArtisanServiceProvider::class);
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
$app->register(Laravel\Tinker\TinkerServiceProvider::class);

// Default providers of Lumen
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);

// Additional libraries
$app->register(Cryental\StackPath\TrustedProxyServiceProvider::class);
$app->register(\Monicahq\Cloudflare\TrustedProxyServiceProvider::class);
$app->register(Hhxsv5\LaravelS\Illuminate\LaravelSServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->configure('app');

$app->middleware([
    App\Http\Middleware\TrustProxies::class,
    App\Http\Middleware\CorsMiddleware::class,
]);

$app->routeMiddleware([
    'throttle'      => ThrottleRequests::class,
]);

$app->router->group([
    'namespace'  => 'App\Http\Controllers',
    'middleware' => 'throttle:api',
], function ($router) {
    require __DIR__.'/../routes/api.php';
});

$app->instance('path.config', app()->basePath().DIRECTORY_SEPARATOR.'config');

return $app;
