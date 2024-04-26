<?php

namespace App\Providers;

use App\Helpers\OhysBlacklistHelper;
use Illuminate\Support\ServiceProvider;

class OhysBlacklistProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OhysBlacklistHelper::class, function ($app) {
            return new OhysBlacklistHelper();
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
