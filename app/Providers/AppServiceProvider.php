<?php

namespace App\Providers;

use App\Providers\DatabaseApplicationProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Contracts\ApplicationProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the database application provider
        $this->app->bind(ApplicationProvider::class, DatabaseApplicationProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
