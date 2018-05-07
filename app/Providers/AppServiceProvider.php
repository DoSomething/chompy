<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Horizon Dashboard Authentication.
        \Horizon::auth(function ($request) {
            if (auth()->user()) {
                return auth()->user()->role === 'user' || auth()->user()->role === 'admin' || auth()->user()->role === 'staff';
            }

            return false;
        });

        View::share('pusherKey', env('PUSHER_APP_KEY'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
