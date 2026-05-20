<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen app_secret to TEXT so encrypted payloads (typically 280-400+
     * chars) fit. The original VARCHAR(255) overflowed once the model
     * adopted the 'encrypted' cast.
     */
    public function up(): void
    {
        Schema::table('reverb_apps', function (Blueprint $table) {
            $table->text('app_secret')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reverb_apps', function (Blueprint $table) {
            $table->string('app_secret')->change();
        });
    }
};
