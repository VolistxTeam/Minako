<?php

namespace App\Providers;

use App\Helpers\KeysCenter;
use Illuminate\Support\ServiceProvider;

class KeysServiceProvider extends ServiceProvider
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
        $this->app->bind('Keys', function () {
            return new KeysCenter();
        });
    }
}
