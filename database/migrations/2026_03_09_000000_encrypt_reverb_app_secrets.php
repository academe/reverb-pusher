<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // Widen the column first. The original VARCHAR(255) cannot hold the
        // ciphertext: a 36- or 39-character secret encrypts to 256 characters.
        // On MySQL in strict mode the first such UPDATE fails ("Data too
        // long"), and nothing is rolled back because MySQL migrations are not
        // transactional, leaving plaintext secrets behind a model that now
        // decrypts them. Without strict mode MySQL silently truncates the
        // ciphertext instead and the secret is lost (#7).
        //
        // The widen migration dated 2026_05_20 does the same change, but it
        // runs after this one, which is too late. It is left in place: it is
        // a no-op where this has already made the column TEXT, and installs
        // that ran both before this fix never run either again.
        Schema::table('reverb_apps', function (Blueprint $table) {
            $table->text('app_secret')->change();
        });

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
