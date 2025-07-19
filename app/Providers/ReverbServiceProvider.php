<?php

namespace App\Providers;

use App\Models\ReverbApp;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Application;

class ReverbServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the database application provider
        $this->app->singleton(ApplicationProvider::class, function ($app) {
            return new class implements ApplicationProvider {
                public function findById(string $id): ?Application
                {
                    $app = ReverbApp::where('app_id', $id)
                        ->where('is_active', true)
                        ->first();

                    if (!$app) {
                        return null;
                    }

                    return new Application(
                        id: $app->app_id,
                        key: $app->app_key,
                        secret: $app->app_secret,
                        capacity: $app->max_connections,
                        allowedOrigins: $app->allowed_origins ?? ['*'],
                        pingInterval: 30,
                        maxMessageSize: 10000,
                    );
                }

                public function findByKey(string $key): ?Application
                {
                    $app = ReverbApp::where('app_key', $key)
                        ->where('is_active', true)
                        ->first();

                    if (!$app) {
                        return null;
                    }

                    return new Application(
                        id: $app->app_id,
                        key: $app->app_key,
                        secret: $app->app_secret,
                        capacity: $app->max_connections,
                        allowedOrigins: $app->allowed_origins ?? ['*'],
                        pingInterval: 30,
                        maxMessageSize: 10000,
                    );
                }

                public function findBySecret(string $secret): ?Application
                {
                    $app = ReverbApp::where('app_secret', $secret)
                        ->where('is_active', true)
                        ->first();

                    if (!$app) {
                        return null;
                    }

                    return new Application(
                        id: $app->app_id,
                        key: $app->app_key,
                        secret: $app->app_secret,
                        capacity: $app->max_connections,
                        allowedOrigins: $app->allowed_origins ?? ['*'],
                        pingInterval: 30,
                        maxMessageSize: 10000,
                    );
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
