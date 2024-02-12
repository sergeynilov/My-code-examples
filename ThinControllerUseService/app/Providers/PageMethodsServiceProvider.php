<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Library\Services\PageMethods;

class PageMethodsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Library\Services\PageMethodsServiceInterface', 'App\Library\Services\PageMethods');
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
