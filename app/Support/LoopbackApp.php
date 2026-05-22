<?php

namespace App\Support;

class LoopbackApp
{
    public static function appId(): string
    {
        return 'loopback-'.substr(self::hash(), 0, 8);
    }

    public static function key(): string
    {
        return 'loopback-'.substr(self::hash(), 0, 16);
    }

    public static function secret(): string
    {
        return substr(self::hash(), 16, 32);
    }

    /**
     * Config array injected into Reverb's apps list at runtime.
     * allowed_origins is restricted to the app's own domain so the
     * loopback app cannot be used from an external origin.
     */
    public static function reverbConfig(): array
    {
        return [
            'app_id' => self::appId(),
            'key' => self::key(),
            'secret' => self::secret(),
            'allowed_origins' => [parse_url(config('app.url'), PHP_URL_HOST) ?: '*'],
            'enable_client_messages' => true,
            'ping_interval' => 30,
            'activity_timeout' => 30,
            'max_message_size' => 10_000,
        ];
    }

    /**
     * Broadcasting connection config for the PHP-side broadcaster.
     * Uses the same host/port/scheme as the primary reverb connection
     * so the loopback test exercises the real configuration.
     */
    public static function broadcastingConnection(): array
    {
        $primary = config('broadcasting.connections.reverb', []);

        return [
            'driver' => 'reverb',
            'key' => self::key(),
            'secret' => self::secret(),
            'app_id' => self::appId(),
            'options' => $primary['options'] ?? [],
            'client_options' => $primary['client_options'] ?? [],
        ];
    }

    /** HMAC derived from APP_KEY so credentials are deterministic and secret. */
    private static function hash(): string
    {
        return hash_hmac('sha256', 'loopback', config('app.key', ''));
    }
}
