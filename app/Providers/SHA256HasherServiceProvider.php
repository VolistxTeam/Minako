<?php

namespace App\Providers;

use App\Helpers\SHA256HasherHelper;
use Illuminate\Support\ServiceProvider;

class SHA256HasherServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SHA256HasherHelper::class, function ($app) {
            return new SHA256HasherHelper();
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
