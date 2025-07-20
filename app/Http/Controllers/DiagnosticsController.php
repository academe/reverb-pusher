<?php

namespace App\Http\Controllers;

use App\Models\ReverbApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnosticsController extends Controller
{
    public function __construct()
    {
        // Ensure user is authenticated for all methods
        $this->middleware('auth');
    }

    public function dashboard()
    {
        // Only show masked app keys for security
        $apps = ReverbApp::where('is_active', true)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'app_key' => $app->app_key,
                    'app_key_masked' => substr($app->app_key, 0, 8) . '...' . substr($app->app_key, -4),
                    'is_active' => $app->is_active,
                ];
            });
            
        $totalApps = ReverbApp::count();
        $activeApps = ReverbApp::where('is_active', true)->count();
        
        return view('diagnostics.dashboard', compact('apps', 'totalApps', 'activeApps'));
    }

    public function testConnection(Request $request)
    {
        $appKey = $request->input('app_key');
        $app = ReverbApp::where('app_key', $appKey)->first();
        
        if (!$app) {
            return response()->json(['error' => 'App not found'], 404);
        }

        $results = [
            'app_found' => true,
            'app_active' => $app->is_active,
            'app_name' => $app->name,
            'app_key_masked' => substr($app->app_key, 0, 8) . '...' . substr($app->app_key, -4),
            'websocket_url' => "wss://{$request->getHost()}/app/[app-key]", // Don't expose real key
            'tests' => []
        ];

        // Test 1: Basic HTTP connectivity
        try {
            $response = Http::timeout(5)->get("https://{$request->getHost()}/health");
            $results['tests']['http_health'] = [
                'status' => 'pass',
                'response_code' => $response->status()
            ];
        } catch (\Exception $e) {
            $results['tests']['http_health'] = [
                'status' => 'fail',
                'error' => 'Connection failed'
            ];
        }

        // Test 2: WebSocket endpoint availability  
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://{$request->getHost()}/app/{$appKey}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Connection: Upgrade',
                'Upgrade: websocket',
                'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==',
                'Sec-WebSocket-Version: 13'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $results['tests']['websocket_handshake'] = [
                'status' => $httpCode === 101 ? 'pass' : 'fail',
                'http_code' => $httpCode,
                'expected' => 101
            ];
        } catch (\Exception $e) {
            $results['tests']['websocket_handshake'] = [
                'status' => 'fail',
                'error' => 'Test failed'
            ];
        }

        return response()->json($results);
    }

    public function sendTestBroadcast(Request $request)
    {
        $appKey = $request->input('app_key');
        $channel = $request->input('channel', 'test-channel');
        $message = $request->input('message', 'Test message from diagnostics');

        $app = ReverbApp::where('app_key', $appKey)->first();
        
        if (!$app) {
            return response()->json(['error' => 'App not found'], 404);
        }

        try {
            // Log the broadcast attempt (with masked key)
            Log::info('Diagnostics: Sending test broadcast', [
                'app_name' => $app->name,
                'app_key_masked' => substr($app->app_key, 0, 8) . '...',
                'channel' => $channel,
                'user' => auth()->user()->email,
            ]);

            // Send test broadcast
            broadcast(new \Illuminate\Broadcasting\BroadcastEvent([
                'type' => 'diagnostic_test',
                'message' => $message,
                'timestamp' => now()->toISOString(),
                'app_name' => $app->name
            ]))->toOthers();

            return response()->json([
                'status' => 'success',
                'message' => 'Test broadcast sent',
                'details' => [
                    'app_name' => $app->name,
                    'channel' => $channel,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Diagnostics: Broadcast failed', [
                'app_name' => $app->name,
                'error' => $e->getMessage(),
                'user' => auth()->user()->email,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Broadcast failed',
                'error' => 'Internal error occurred'
            ], 500);
        }
    }

    public function reverbStatus()
    {
        // Check if Reverb process is running
        $reverbRunning = false;
        $processInfo = 'Status check available';

        try {
            $output = shell_exec('supervisorctl status reverb-server 2>&1');
            $reverbRunning = strpos($output, 'RUNNING') !== false;
            // Don't expose detailed process info for security
            $processInfo = $reverbRunning ? 'Process running normally' : 'Process not running';
        } catch (\Exception $e) {
            $processInfo = 'Could not check process status';
        }

        return response()->json([
            'reverb_running' => $reverbRunning,
            'process_info' => $processInfo,
            'timestamp' => now()->toISOString()
        ]);
    }
}
