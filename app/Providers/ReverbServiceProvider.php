<?php

namespace App\Providers;

use App\Models\ReverbApp;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Application;
use Laravel\Reverb\Exceptions\InvalidApplication;

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
                public function all(): Collection
                {
                    Log::info("Reverb: Loading all active apps");
                    
                    $apps = ReverbApp::where('is_active', true)->get();
                    
                    return $apps->map(function ($app) {
                        return new Application(
                            id: $app->app_id,
                            key: $app->app_key,
                            secret: $app->app_secret,
                            pingInterval: 30,
                            activityTimeout: 30,
                            allowedOrigins: $app->allowed_origins ?? ['*'],
                            maxMessageSize: 10000,
                            options: [],
                        );
                    });
                }

                public function findById(string $id): Application
                {
                    Log::info("Reverb: Looking up app by ID: {$id}");
                    
                    $app = ReverbApp::where('app_id', $id)
                        ->where('is_active', true)
                        ->first();

                    if (!$app) {
                        Log::warning("Reverb: No active app found for ID: {$id}");
                        throw new InvalidApplication("Application with ID '{$id}' not found or inactive.");
                    }

                    Log::info("Reverb: Found app", ['app_id' => $app->app_id, 'name' => $app->name]);
                    
                    return new Application(
                        id: $app->app_id,
                        key: $app->app_key,
                        secret: $app->app_secret,
                        pingInterval: 30,
                        activityTimeout: 30,
                        allowedOrigins: $app->allowed_origins ?? ['*'],
                        maxMessageSize: 10000,
                        options: [],
                    );
                }

                public function findByKey(string $key): Application
                {
                    Log::info("Reverb: Looking up app by key: {$key}");
                    
                    $app = ReverbApp::where('app_key', $key)
                        ->where('is_active', true)
                        ->first();

                    if (!$app) {
                        Log::warning("Reverb: No active app found for key: {$key}");
                        throw new InvalidApplication("Application with key '{$key}' not found or inactive.");
                    }

                    Log::info("Reverb: Found app", ['app_id' => $app->app_id, 'name' => $app->name]);
                    
                    return new Application(
                        id: $app->app_id,
                        key: $app->app_key,
                        secret: $app->app_secret,
                        pingInterval: 30,
                        activityTimeout: 30,
                        allowedOrigins: $app->allowed_origins ?? ['*'],
                        maxMessageSize: 10000,
                        options: [],
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
