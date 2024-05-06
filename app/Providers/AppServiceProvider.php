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
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerAuth();
    }

    public function registerAuth(): void
    {
        $this->app->scoped(AuthHelper::class, function ($app) {
            return new AuthHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $requestBypassSecret = $request->input('secret', null);
            if ($requestBypassSecret != config('minako.secret')) {
                return Limit::perMinute(200)->by($request->getClientIp());
            } else {
                return Limit::none();
            }
        });
    }
}
