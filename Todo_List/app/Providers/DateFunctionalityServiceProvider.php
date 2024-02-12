<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DateFunctionalityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Library\Services\DateFunctionalityServiceInterface', 'App\Library\Services\DateFunctionality');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
