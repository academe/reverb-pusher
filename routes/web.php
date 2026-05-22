<?php

use App\Support\LoopbackApp;
use Illuminate\Http\Request;
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
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'services' => [
                'database' => 'ok',
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Health check failed', ['error' => $e->getMessage()]);

        return response()->json([
            'status' => 'unhealthy',
        ], 500);
    }
});

// Channel auth for the loopback diagnostics app.
// Must sign with the loopback secret so Reverb can verify using its own app record.
Route::post('/reverb/loopback-auth', function (Request $request) {
    $socketId = $request->string('socket_id')->toString();
    $channelName = $request->string('channel_name')->toString();

    if (! $socketId || ! $channelName) {
        return response()->json(['error' => 'Missing socket_id or channel_name.'], 400);
    }

    $signature = hash_hmac('sha256', "{$socketId}:{$channelName}", LoopbackApp::secret());

    return response()->json(['auth' => LoopbackApp::key().':'.$signature]);
})->middleware(['auth']);

require __DIR__.'/auth.php';
