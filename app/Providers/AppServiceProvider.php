<?php

namespace App\Providers;

use App\Models\ReverbApp;
use App\Models\User;
use App\Observers\ReverbAppObserver;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
