<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReverbApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'app_id',
        'app_key',
        'app_secret',
        'description',
        'is_active',
        'max_connections',
        'allowed_origins',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_origins' => 'array',
            'app_secret' => 'encrypted',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($app) {
            if (empty($app->app_id)) {
                $app->app_id = 'app-'.Str::random(8);
            }
            if (empty($app->app_key)) {
                $app->app_key = 'key-'.Str::random(16);
            }
            if (empty($app->app_secret)) {
                $app->app_secret = Str::random(64);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Reduce a pasted origin to a bare domain (or '*').
     *
     * Reverb's allowed_origins config expects hostnames, not full URLs.
     * Pasting "https://example.com/path" returns "example.com".
     */
    public static function normalizeOrigin(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($value === '*') {
            return '*';
        }

        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                return strtolower($host);
            }
        }

        $value = preg_replace('~[/?#].*$~', '', $value);

        return strtolower($value);
    }
}
