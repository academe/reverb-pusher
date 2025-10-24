<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

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
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});

require __DIR__.'/auth.php';
