<?php

namespace App\Http\Controllers;

use App\Models\ReverbApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnosticsController extends Controller
{
    public function dashboard()
    {
        $apps = ReverbApp::where('is_active', true)->get();
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
            'websocket_url' => "wss://{$request->getHost()}/app/{$appKey}",
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
                'error' => $e->getMessage()
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
                'error' => $e->getMessage()
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
            // Log the broadcast attempt
            Log::info('Diagnostics: Sending test broadcast', [
                'app_id' => $app->app_id,
                'channel' => $channel,
                'message' => $message
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
                'app_id' => $app->app_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Broadcast failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reverbStatus()
    {
        // Check if Reverb process is running
        $reverbRunning = false;
        $processInfo = null;

        try {
            $output = shell_exec('supervisorctl status reverb-server 2>&1');
            $reverbRunning = strpos($output, 'RUNNING') !== false;
            $processInfo = trim($output);
        } catch (\Exception $e) {
            $processInfo = 'Could not check process status: ' . $e->getMessage();
        }

        return response()->json([
            'reverb_running' => $reverbRunning,
            'process_info' => $processInfo,
            'timestamp' => now()->toISOString()
        ]);
    }
}
