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
        'allowed_origins'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_origins' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($app) {
            if (empty($app->app_id)) {
                $app->app_id = 'app-' . Str::random(8);
            }
            if (empty($app->app_key)) {
                $app->app_key = 'key-' . Str::random(16);
            }
            if (empty($app->app_secret)) {
                $app->app_secret = 'secret-' . Str::random(32);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
