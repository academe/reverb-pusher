<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DiagnosticsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Diagnostics routes
Route::prefix('diagnostics')->group(function () {
    Route::get('/', [DiagnosticsController::class, 'dashboard'])->name('diagnostics.dashboard');
    Route::post('/test-connection', [DiagnosticsController::class, 'testConnection']);
    Route::post('/send-test-broadcast', [DiagnosticsController::class, 'sendTestBroadcast']);
    Route::get('/reverb-status', [DiagnosticsController::class, 'reverbStatus']);
});

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
