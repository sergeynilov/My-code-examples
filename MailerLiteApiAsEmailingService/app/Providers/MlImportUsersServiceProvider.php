<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MlImportUsersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->bind('App\Library\Services\MlImportUsersInterface',
                'App\Library\Services\FakeImportUsers');
        } else {
            $this->app->bind('App\Library\Services\MlImportUsersInterface',
                'App\Library\Services\MlImportUsers');
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
