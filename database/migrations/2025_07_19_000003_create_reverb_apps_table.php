<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reverb_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_id')->unique();
            $table->string('app_key')->unique();
            $table->string('app_secret');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_connections')->default(1000);
            $table->json('allowed_origins')->nullable();
            $table->timestamps();
            
            $table->index(['app_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reverb_apps');
    }
};
