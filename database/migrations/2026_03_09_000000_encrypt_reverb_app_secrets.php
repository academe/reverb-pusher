<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt existing plaintext app_secret values.
     *
     * The ReverbApp model now uses the 'encrypted' cast on app_secret.
     * This migration encrypts existing plaintext values at the database
     * level, bypassing the model entirely to avoid cast conflicts.
     */
    public function up(): void
    {
        $rows = DB::table('reverb_apps')->get(['id', 'app_secret']);

        foreach ($rows as $row) {
            // Skip if already encrypted (Laravel encrypted payloads are base64-encoded JSON)
            if (str_starts_with($row->app_secret, 'eyJ')) {
                continue;
            }

            DB::table('reverb_apps')
                ->where('id', $row->id)
                ->update(['app_secret' => Crypt::encryptString($row->app_secret)]);
        }
    }

    /**
     * Decrypt secrets back to plaintext.
     */
    public function down(): void
    {
        $rows = DB::table('reverb_apps')->get(['id', 'app_secret']);

        foreach ($rows as $row) {
            try {
                $decrypted = Crypt::decryptString($row->app_secret);

                DB::table('reverb_apps')
                    ->where('id', $row->id)
                    ->update(['app_secret' => $decrypted]);
            } catch (\Exception $e) {
                // Already plaintext, skip
            }
        }
    }
};
