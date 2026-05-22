<?php

namespace App\Filament\Admin\Pages;

use App\Support\LoopbackApp;
use Filament\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReverbDiagnostics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Diagnostics';

    protected static ?string $title = 'Reverb Diagnostics';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.admin.pages.reverb-diagnostics';

    private const WORDS = [
        'apple', 'river', 'cloud', 'sunset', 'forest', 'ocean',
        'candle', 'amber', 'silver', 'copper', 'hollow', 'gentle',
        'cosmic', 'serene', 'velvet', 'mossy', 'crisp', 'radiant',
    ];

    public function sendFromBackend(): void
    {
        $word = self::WORDS[array_rand(self::WORDS)];
        $timestamp = now()->toTimeString();

        // Direct, signed POST to Reverb's HTTP API so we can see exactly
        // what Reverb returns. This mirrors what Laravel's Reverb broadcaster
        // does under the hood, but exposes the response status and body so
        // a silent failure cannot hide behind a successful Livewire request.
        $probe = $this->postEventToReverb($word, $timestamp);

        Log::info('Loopback: HTTP API probe result', $probe);

        if ($probe['ok']) {
            $this->dispatch('backend-sent', word: $word, timestamp: $timestamp);

            return;
        }

        $this->dispatch('backend-error', message: $probe['message']);
    }

    /**
     * Send a Pusher-signed event POST directly to Reverb's HTTP API.
     * Returns the response so failures can be surfaced to the UI.
     *
     * @return array{ok: bool, status: ?int, body: ?string, url: string, message: string}
     */
    protected function postEventToReverb(string $word, string $timestamp): array
    {
        $appId = LoopbackApp::appId();
        $key = LoopbackApp::key();
        $secret = LoopbackApp::secret();

        $host = (string) config('reverb.servers.reverb.host', '127.0.0.1');
        if (in_array($host, ['0.0.0.0', ''], true)) {
            $host = '127.0.0.1';
        }
        $port = (int) config('reverb.servers.reverb.port', 8080);

        $body = json_encode([
            'name' => 'loopback.message',
            'data' => json_encode([
                'source' => 'backend',
                'word' => $word,
                'timestamp' => $timestamp,
            ]),
            'channels' => ['private-loopback'],
        ]);

        $path = "/apps/{$appId}/events";
        $params = [
            'auth_key' => $key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($params);
        $queryString = http_build_query($params);

        $signature = hash_hmac('sha256', "POST\n{$path}\n{$queryString}", $secret);
        $url = "http://{$host}:{$port}{$path}?{$queryString}&auth_signature={$signature}";

        try {
            $response = Http::timeout(3)
                ->connectTimeout(2)
                ->withBody($body, 'application/json')
                ->post($url);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                    'url' => $url,
                    'message' => 'OK',
                ];
            }

            return [
                'ok' => false,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
                'url' => $url,
                'message' => "Reverb HTTP API returned {$response->status()}: ".substr($response->body(), 0, 200),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'url' => $url,
                'message' => 'Could not POST to Reverb: '.$e->getMessage(),
            ];
        }
    }

    protected function getViewData(): array
    {
        $request = app(Request::class);
        $host = $request->getHost();
        $port = $request->getPort();
        $tls = $request->isSecure();

        return [
            'loopbackKey' => LoopbackApp::key(),
            'wsHost' => $host,
            'wsPort' => $port,
            'wssPort' => $port,
            'forceTLS' => $tls,
            'proxyCheck' => $this->checkWebSocketProxy($host, $port, $tls),
        ];
    }

    /**
     * Probe the WebSocket URL the browser will use, sending an HTTP request
     * that asks for a WebSocket upgrade. We read only the status line so we
     * don't hang waiting for WebSocket frames after a successful 101.
     *
     * @return array{ok: bool, status: ?int, url: string, message: string}
     */
    protected function checkWebSocketProxy(string $host, int $port, bool $tls): array
    {
        $scheme = $tls ? 'https' : 'http';
        $path = '/app/'.LoopbackApp::key();
        $url = "{$scheme}://{$host}:{$port}{$path}";
        $reverbPort = (int) config('reverb.servers.reverb.port', 8080);

        $transport = $tls ? "tls://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client($transport, $errno, $errstr, 2.0, STREAM_CLIENT_CONNECT, $context);

        if ($socket === false) {
            return [
                'ok' => false,
                'status' => null,
                'url' => $url,
                'message' => "Could not connect to {$transport}: {$errstr} (errno {$errno})",
            ];
        }

        try {
            stream_set_timeout($socket, 2);

            $request = "GET {$path} HTTP/1.1\r\n"
                ."Host: {$host}\r\n"
                ."Upgrade: websocket\r\n"
                ."Connection: Upgrade\r\n"
                ."Sec-WebSocket-Version: 13\r\n"
                .'Sec-WebSocket-Key: '.base64_encode(random_bytes(16))."\r\n"
                ."\r\n";

            fwrite($socket, $request);

            $statusLine = fgets($socket, 1024);

            if ($statusLine === false || ! preg_match('#^HTTP/[\d.]+ (\d+)#', $statusLine, $m)) {
                return [
                    'ok' => false,
                    'status' => null,
                    'url' => $url,
                    'message' => 'No HTTP response from the WebSocket endpoint.',
                ];
            }

            $status = (int) $m[1];

            if ($status === 101) {
                return [
                    'ok' => true,
                    'status' => 101,
                    'url' => $url,
                    'message' => 'WebSocket upgrade succeeded — the proxy is forwarding to Reverb correctly.',
                ];
            }

            if ($status === 404) {
                return [
                    'ok' => false,
                    'status' => 404,
                    'url' => $url,
                    'message' => "Your web server returned 404 for the WebSocket path. Herd/nginx is not proxying /app/* through to Reverb on port {$reverbPort}. Add an nginx location block that forwards upgrade requests for /app to localhost:{$reverbPort}. Proxying /apps/* as well is only needed if PHP also reaches Reverb through nginx rather than directly on localhost.",
                ];
            }

            if ($status >= 500) {
                return [
                    'ok' => false,
                    'status' => $status,
                    'url' => $url,
                    'message' => "Web server returned {$status}. The proxy looks configured but Reverb on port {$reverbPort} may not be running. Check `php artisan reverb:start`.",
                ];
            }

            return [
                'ok' => false,
                'status' => $status,
                'url' => $url,
                'message' => "Unexpected HTTP {$status} from the WebSocket endpoint.",
            ];
        } finally {
            fclose($socket);
        }
    }
}
