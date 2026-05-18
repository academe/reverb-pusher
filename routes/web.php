<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/health', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();

        // Count active WebSocket apps
        $activeApps = \App\Models\ReverbApp::where('is_active', true)->count();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'services' => [
                'database' => 'ok',
                'reverb' => 'running',
                'active_apps' => $activeApps,
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Health check failed', ['error' => $e->getMessage()]);

        return response()->json([
            'status' => 'unhealthy',
        ], 500);
    }
});

require __DIR__.'/auth.php';
