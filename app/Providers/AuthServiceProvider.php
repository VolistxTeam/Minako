<?php

namespace App\Providers;

use App\Helpers\AuthHelper;
use App\Helpers\HttpClientHelper;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped(AuthHelper::class, function ($app) {
            return new AuthHelper();
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
