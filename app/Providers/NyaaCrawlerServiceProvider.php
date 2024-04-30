<?php

namespace App\Providers;

use App\Helpers\NyaaCrawlerHelper;
use Illuminate\Support\ServiceProvider;

class NyaaCrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped(NyaaCrawlerHelper::class, function ($app) {
            return new NyaaCrawlerHelper();
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
