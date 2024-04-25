<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: ''
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Monicahq\Cloudflare\Http\Middleware\TrustProxies::class
        );
        $middleware->api(remove: [
            StartSession::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
        ]);
        $middleware->alias([
            'CacheResponse' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
        ]);
        $middleware->trustProxies(at: [
            '127.0.0.1',
            'localhost',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->noContent(404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->noContent(405);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->noContent(429);
        });

        $exceptions->render(function (ErrorException $e, Request $request) {
            return response()->view('errors/500', 500)->noContent(200);
        });
    })->create();
