<?php

use App\Filament\Admin\Pages\ReverbDiagnostics;
use App\Models\User;
use App\Support\LoopbackApp;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// ── LoopbackApp helper ────────────────────────────────────────────────────────

describe('LoopbackApp', function () {
    it('derives deterministic credentials from APP_KEY', function () {
        $key = LoopbackApp::key();
        $secret = LoopbackApp::secret();
        $appId = LoopbackApp::appId();

        expect($key)->toStartWith('loopback-')
            ->and($key)->toBe(LoopbackApp::key())
            ->and($secret)->toBeString()->toHaveLength(32)
            ->and($appId)->toStartWith('loopback-');
    });

    it('restricts allowed_origins to the app domain', function () {
        $config = LoopbackApp::reverbConfig();

        expect($config['allowed_origins'])->toBeArray()->not->toBeEmpty()
            ->and($config['enable_client_messages'])->toBeTrue();
    });

    it('inherits host options from the primary reverb broadcasting connection', function () {
        config(['broadcasting.connections.reverb.options' => ['host' => 'reverb.test', 'port' => 9090]]);

        $conn = LoopbackApp::broadcastingConnection();

        expect($conn['options']['host'])->toBe('reverb.test')
            ->and($conn['options']['port'])->toBe(9090)
            ->and($conn['key'])->toBe(LoopbackApp::key())
            ->and($conn['secret'])->toBe(LoopbackApp::secret());
    });
});

// ── Loopback channel-auth endpoint ───────────────────────────────────────────

describe('POST /reverb/loopback-auth', function () {
    it('requires authentication', function () {
        $this->postJson('/reverb/loopback-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-loopback',
        ])->assertUnauthorized();
    });

    it('returns a correctly signed auth token for an authenticated user', function () {
        $user = User::factory()->create(['active' => true]);

        $socketId = '123.456';
        $channelName = 'private-loopback';

        $response = $this->actingAs($user)
            ->postJson('/reverb/loopback-auth', [
                'socket_id' => $socketId,
                'channel_name' => $channelName,
            ]);

        $response->assertOk()->assertJsonStructure(['auth']);

        $expected = LoopbackApp::key().':'.hash_hmac('sha256', "{$socketId}:{$channelName}", LoopbackApp::secret());

        expect($response->json('auth'))->toBe($expected);
    });

    it('returns 400 when socket_id or channel_name is missing', function () {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)
            ->postJson('/reverb/loopback-auth', [])
            ->assertStatus(400);
    });
});

// ── ReverbDiagnostics Filament page ──────────────────────────────────────────

describe('ReverbDiagnostics page', function () {
    it('requires authentication', function () {
        $this->get('/admin/reverb-diagnostics')->assertRedirect('/login');
    });

    it('loads for an authenticated active user', function () {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)
            ->get('/admin/reverb-diagnostics')
            ->assertSuccessful();
    });

    it('sendFromBackend posts a signed event to Reverb', function () {
        Http::fake([
            '*/apps/*/events*' => Http::response('{}', 200),
        ]);

        $user = User::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(ReverbDiagnostics::class)
            ->call('sendFromBackend')
            ->assertDispatched('backend-sent');

        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/apps/'.LoopbackApp::appId().'/events')
                && str_contains($request->url(), 'auth_signature=')
                && str_contains($request->body(), '"channels":["private-loopback"]')
                && str_contains($request->body(), '"name":"loopback.message"');
        });
    });

    it('sendFromBackend surfaces Reverb errors to the UI', function () {
        Http::fake([
            '*/apps/*/events*' => Http::response('{"error":"Invalid signature"}', 401),
        ]);

        $user = User::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(ReverbDiagnostics::class)
            ->call('sendFromBackend')
            ->assertDispatched('backend-error');
    });
});
