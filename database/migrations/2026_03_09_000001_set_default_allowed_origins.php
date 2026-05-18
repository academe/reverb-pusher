<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set allowed_origins to ["*"] for existing apps that have no origins configured.
     *
     * The application now requires explicit origin configuration. This migration
     * preserves the previous permissive behaviour for existing apps so upgrades
     * don't silently break WebSocket connections.
     */
    public function up(): void
    {
        DB::table('reverb_apps')
            ->where(function ($query) {
                $query->whereNull('allowed_origins')
                    ->orWhere('allowed_origins', '')
                    ->orWhere('allowed_origins', 'null')
                    ->orWhereJsonLength('allowed_origins', 0);
            })
            ->update(['allowed_origins' => json_encode(['*'])]);
    }

    /**
     * No rollback needed — the previous default was effectively ["*"].
     */
    public function down(): void
    {
        //
    }
};
