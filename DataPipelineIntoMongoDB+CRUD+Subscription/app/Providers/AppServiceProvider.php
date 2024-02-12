<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Category;
use App\Models\BadWord;
use App\Models\Subscription;
use Illuminate\Support\ServiceProvider;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        if ($this->app->environment('local')) {
            $this->app->bind('App\Library\Services\Interfaces\UploadedFileManagementInterface',
                'App\Library\Services\LocalStorageUploadedFileManagement');
        }
        else {
            $this->app->bind('App\Library\Services\Interfaces\UploadedFileManagementInterface',
                'App\Library\Services\AwsS3StorageUploadedFileManagement');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Validator::extend('checkSubscriptionUnique', function ($attribute, $value, $parameters, $validator) {
            $subscriptionId = $parameters[0] ?? null;
            $subscriptionsCount = Subscription::getSimilarSubscriptionByTitle($value, $subscriptionId, true);
            return $subscriptionsCount == 0;
        });

        Validator::extend('checkProductUnique', function ($attribute, $value, $parameters, $validator) {
            $productId = $parameters[0] ?? null;
            $productsCount = Product::getSimilarProductByTitle($value, $productId, true);
            return $productsCount == 0;
        });

        Validator::extend('checkCategoryUnique', function ($attribute, $value, $parameters, $validator) {
            $categoryId = $parameters[0] ?? null;
            $categoriesCount = Category::getSimilarCategoryByName($value, $categoryId, true);
            return $categoriesCount == 0;
        });

        Validator::extend('checkBadWordUnique', function ($attribute, $value, $parameters, $validator) {
            $badWordId = $parameters[0] ?? null;
            $badWordsCount = BadWord::getSimilarBadWordByWord($value, $badWordId, true);
            return $badWordsCount == 0;
        });

    }
}
