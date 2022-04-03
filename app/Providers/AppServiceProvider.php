<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interface\DatabaseProvider;
use App\Implementation\MysqlDatabaseProvider;
use App\Interface\StreamDataProviderAPI;
use App\Implementation\TwitchStreamDataProviderAPI;
use App\Implementation\MockStreamDataProviderAPI;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DatabaseProvider::class, MysqlDatabaseProvider::class);
        $this->app->bind(StreamDataProviderAPI::class, TwitchStreamDataProviderAPI::class);
        //$this->app->bind(StreamDataProviderAPI::class, MockStreamDataProviderAPI::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
