<?php

namespace App\Providers;

use App\Helpers\HttpClientHelper;
use Illuminate\Support\ServiceProvider;

class HttpClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped(HttpClientHelper::class, function ($app) {
            return new HttpClientHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
