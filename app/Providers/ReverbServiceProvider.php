<?php

namespace App\Providers;

use App\Models\ReverbApp;
use App\Observers\ReverbAppObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ReverbServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Empty - we'll modify config in boot
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            ReverbApp::observe(ReverbAppObserver::class);
            $this->loadAppsFromDatabase();
        });
    }

    /**
     * Load apps from database and update Reverb config
     */
    private function loadAppsFromDatabase(): void
    {
        try {
            Log::debug('Reverb: Loading apps from database');

            $apps = ReverbApp::where('is_active', true)->get();

            $reverbApps = $apps->map(function ($app) {
                return [
                    'key' => $app->app_key,
                    'secret' => $app->app_secret,
                    'app_id' => $app->app_id,
                    'options' => [
                        'host' => config('reverb.servers.reverb.hostname'),
                        'port' => config('reverb.servers.reverb.port', 443),
                        'scheme' => config('reverb.servers.reverb.scheme', 'https'),
                    ],
                    'allowed_origins' => $app->allowed_origins ?? [],
                    'ping_interval' => 30,
                    'activity_timeout' => 30,
                    'max_message_size' => 10000,
                ];
            })->toArray();

            // Update the config at runtime
            config(['reverb.apps.apps' => $reverbApps]);

            Log::debug('Reverb: Loaded '.count($reverbApps).' apps from database');

        } catch (\Exception $e) {
            Log::warning('Reverb: Could not load apps from database', ['error' => $e->getMessage()]);
            // Keep empty array as fallback
            config(['reverb.apps.apps' => []]);
        }
    }
}
