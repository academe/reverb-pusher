<?php

namespace App\Providers;

use App\Models\ReverbApp;
use App\Observers\ReverbAppObserver;
use App\Support\ActiveReverbApps;
use App\Support\LoopbackApp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Sleep;
use RuntimeException;

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
     *
     * Every process that boots the application loads the apps, but only the
     * reverb:start server may wait for them. The server reads them once and
     * keeps them for its whole life, and it has nothing to serve until it has
     * them, so a wait there costs nothing. Everything else (web requests, the
     * admin panel, queue workers, other artisan commands) runs this same code
     * on every boot but does not accept connections, so a wait there would
     * stall each request or command for nothing. Those keep warning and
     * carrying on.
     *
     * runningConsoleCommand() is false for any process not running in the
     * console, and otherwise compares the first command-line argument with the
     * command name. It matches `artisan reverb:start --host=... --port=...`.
     * A global option placed before the command name (`artisan --env=...
     * reverb:start`) hides it, and the server then falls back to the
     * warn-and-carry-on path.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            ReverbApp::observe(ReverbAppObserver::class);

            if ($this->app->runningConsoleCommand('reverb:start')) {
                $this->loadAppsForServer();
            } else {
                $this->loadAppsFromDatabase();
            }
        });
    }

    /**
     * Load apps from database and update Reverb config
     */
    private function loadAppsFromDatabase(): void
    {
        try {
            Log::debug('Reverb: Loading apps from database');

            $this->useApps($this->app->make(ActiveReverbApps::class)->fetch());

        } catch (\Exception $e) {
            Log::warning('Reverb: Could not load apps from database', ['error' => $e->getMessage()]);
            // Keep empty array as fallback
            config(['reverb.apps.apps' => []]);
        }
    }

    /**
     * Load the apps for the reverb:start server, waiting for the database
     * with doubling delays if the first attempts fail.
     *
     * The server must never start serving with no apps. It would reject every
     * publish and every client connection until someone restarted it by hand.
     * So when the retry window runs out, this throws instead of carrying on.
     * The console kernel turns an exception thrown while booting into exit
     * code 1, and the process manager then starts a fresh server that tries
     * again.
     *
     * There is deliberately no early exit, not even when the database answers
     * but has no active apps. Supervisor treats a process that exits within
     * `startsecs` of starting as a failed start, and after `startretries`
     * failed starts it marks the program FATAL and stops restarting it. A
     * process that exits after the full window is an ordinary exit, and
     * `autorestart=true` restarts that indefinitely. The length of the window
     * is what turns a database outage into "keep trying" rather than "give
     * up", so keep it well above `startsecs`.
     *
     * @throws RuntimeException when no active apps could be loaded in time
     */
    private function loadAppsForServer(): void
    {
        $retryWindow = max(0, (int) config('reverb.startup.retry_window', 120));
        $maxRetryDelay = max(1, (int) config('reverb.startup.max_retry_delay', 15));
        $activeApps = $this->app->make(ActiveReverbApps::class);

        $startedAt = Carbon::now();
        $secondsSlept = 0;
        $nextDelay = 1;
        $attempt = 0;

        Log::debug('Reverb: Loading apps from database');

        while (true) {
            $attempt++;
            $lastException = null;

            try {
                $apps = $activeApps->fetch();

                // An empty list is a failure here too: serving with no apps is
                // exactly what this guards against, and a table that is empty
                // for a moment (a restore in progress, say) may fill up again.
                if ($apps->isNotEmpty()) {
                    $this->useApps($apps);

                    if ($attempt > 1) {
                        Log::info("Reverb: Loaded apps from database after {$attempt} attempts");
                    }

                    return;
                }

                $reason = 'no active apps';
            } catch (\Exception $e) {
                $lastException = $e;
                $reason = $e->getMessage();
            }

            // The window is measured by the clock, so that slow attempts (a
            // connection can take many seconds to time out) count towards it.
            // Time spent asleep is a floor on the time elapsed. Taking the
            // larger of the two keeps the loop finite when the clock does not
            // move, as under Sleep::fake() in tests.
            $elapsed = max($secondsSlept, (int) $startedAt->diffInSeconds(Carbon::now()));
            $remaining = $retryWindow - $elapsed;

            if ($remaining <= 0) {
                break;
            }

            // Make the next attempt open a new database connection. An
            // attempt that connected but found no active apps leaves its
            // handle cached. If the database then goes away with an error
            // that Laravel does not recognise as a lost connection, every
            // later attempt would reuse that dead handle and could use up the
            // whole window without trying a new one. If no connection was
            // ever made, the next attempt opens a new one either way.
            $activeApps->disconnect();

            $delay = min($nextDelay, $remaining);

            Log::warning('Reverb: Could not load apps from database, retrying', [
                'reason' => $reason,
                'attempt' => $attempt,
                'retry_in_seconds' => $delay,
            ]);

            Sleep::for($delay)->seconds();

            $secondsSlept += $delay;
            $nextDelay = min($nextDelay * 2, $maxRetryDelay);
        }

        Log::error('Reverb: Could not load apps from database, exiting so the server can be restarted', [
            'reason' => $reason,
            'attempts' => $attempt,
            'elapsed_seconds' => $elapsed,
        ]);

        throw new RuntimeException(
            "Reverb: Could not load apps from database within {$retryWindow} seconds: {$reason}",
            previous: $lastException,
        );
    }

    /**
     * Write the given apps, plus the loopback app, into Reverb's config.
     *
     * @param  Collection<int, ReverbApp>  $apps
     */
    private function useApps(Collection $apps): void
    {
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

        // Always append the loopback app — credentials are derived from APP_KEY
        // and allowed_origins is restricted to the app's own domain.
        $reverbApps[] = LoopbackApp::reverbConfig();

        // Update the config at runtime
        config(['reverb.apps.apps' => $reverbApps]);

        Log::debug('Reverb: Loaded '.count($reverbApps).' apps from database');
    }
}
