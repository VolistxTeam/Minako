<?php

namespace App\Providers;

use App\Classes\StringCompareJaroWinkler;
use App\Helpers\StringOperationsHelper;
use Illuminate\Support\ServiceProvider;

class StringOperationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(StringOperationsHelper::class, function ($app) {
            return new StringOperationsHelper(new StringCompareJaroWinkler());
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
