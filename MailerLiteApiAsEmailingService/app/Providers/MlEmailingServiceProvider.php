<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MlEmailingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->bind('App\Library\Services\MlEmailingServiceInterface',
                'App\Library\Services\FakeEmailingService');
        } else {
            $this->app->bind('App\Library\Services\MlEmailingServiceInterface',
                'App\Library\Services\MlEmailingService');
        }
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
