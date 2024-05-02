<?php

namespace App\Providers;

use App\Helpers\DTOUtilsHelper;
use Illuminate\Support\ServiceProvider;

class DTOUtilsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DTOUtilsHelper::class, function ($app) {
            return new DTOUtilsHelper();
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
