<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Http\ResponseFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app(RateLimiter::class)->for('api', function () {
            return Limit::perMinute(500)->by(request()->getClientIp());
        });

        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, function () {
            return new ResponseFactory();
        });
    }
}
