<?php

namespace App\Providers;

use App\Helpers\JikanAPIHelper;
use Illuminate\Support\ServiceProvider;

class JikanAPIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped(JikanAPIHelper::class, function ($app) {
            return new JikanAPIHelper();
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
