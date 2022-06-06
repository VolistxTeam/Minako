<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Request;
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
            $requestBypassSecret = Request::input('secret', null);
            if ($requestBypassSecret != config('minako.secret')) {
                return Limit::perMinute(200)->by(request()->getClientIp());
            }
        });

        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, function () {
            return new ResponseFactory();
        });
    }
}
