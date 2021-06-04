<?php

use App\Http\Middleware\ThrottleRequests;
use jdavidbakr\CloudfrontProxies\CloudfrontProxies;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Torann\GeoIP\GeoIPServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(GeoIPServiceProvider::class);
$app->register(Spatie\ResponseCache\ResponseCacheServiceProvider::class);
$app->register(SwooleTW\Http\LumenServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);

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
    CloudfrontProxies::class,
    App\Http\Middleware\FirewallMiddleware::class,
]);

$app->routeMiddleware([
    'auth.user' => App\Http\Middleware\UserAuthMiddleware::class,
    'auth.admin' => App\Http\Middleware\AdminAuthMiddleware::class,
    'cacheResponse' => CacheResponse::class,
    'throttle' => \LumenRateLimiting\ThrottleRequests::class,
]);

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'middleware' => 'throttle:global',
], function ($router) {
    require __DIR__ . '/../routes/system.php';
    require __DIR__ . '/../routes/api.php';
});

collect(scandir(__DIR__ . '/../config'))->each(function ($item) use ($app) {
    $app->configure(basename($item, '.php'));
});

return $app;
