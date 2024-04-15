<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $requestBypassSecret = $request->input('secret', null);
            if ($requestBypassSecret != config('minako.secret')) {
                return Limit::perMinute(2000)->by($request->getClientIp());
            } else {
                return Limit::none();
            }
        });
    }
}
