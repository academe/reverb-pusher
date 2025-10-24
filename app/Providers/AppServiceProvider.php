<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Providers\DatabaseApplicationProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Contracts\ApplicationProvider;
use App\Models\ReverbApp;
use App\Observers\ReverbAppObserver;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the database application provider
        // $this->app->bind(ApplicationProvider::class, DatabaseApplicationProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // Register the ReverbApp observer
        ReverbApp::observe(ReverbAppObserver::class);
        Log::info('ReverbServiceProvider: Observer registered for ReverbApp model');

        // Register UserPolicy
        Gate::policy(User::class, UserPolicy::class);
    }
}
