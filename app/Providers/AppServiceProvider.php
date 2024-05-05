<?php

namespace App\Providers;

use App\Helpers\AuthHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        $this->registerAuth();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $requestBypassSecret = $request->input('secret', null);
            if ($requestBypassSecret != config('minako.secret')) {
                return Limit::perMinute(200)->by($request->getClientIp());
            } else {
                return Limit::none();
            }
        });

        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    public function registerAuth(): void
    {
        $this->app->scoped(AuthHelper::class, function ($app) {
            return new AuthHelper();
        });
    }
}
