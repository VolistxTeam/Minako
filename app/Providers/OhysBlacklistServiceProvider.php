<?php

namespace App\Providers;

use App\Helpers\OhysBlacklistCenter;
use Illuminate\Support\ServiceProvider;

class OhysBlacklistServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped('OhysBlacklist', function ($app) {
            return $app->make(OhysBlacklistCenter::class);
        });
    }
}
