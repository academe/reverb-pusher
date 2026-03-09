<?php

use App\Models\ReverbApp;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['api.token' => 'test-api-token']);
});

describe('authentication', function () {
    it('rejects requests without a token', function () {
        $this->postJson('/api/reverb-apps', ['name' => 'Test'])
            ->assertUnauthorized();
    });

    it('rejects requests with an invalid token', function () {
        $this->withHeader('Authorization', 'Bearer wrong-token')
            ->postJson('/api/reverb-apps', ['name' => 'Test'])
            ->assertUnauthorized();
    });

    it('rejects requests when no api token is configured', function () {
        config(['api.token' => null]);

        $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', ['name' => 'Test'])
            ->assertUnauthorized();
    });
});

describe('POST /api/reverb-apps', function () {
    it('creates a reverb app with auto-generated credentials', function () {
        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', [
                'name' => 'My Publisher',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'app_id', 'app_key', 'app_secret',
                    'is_active', 'max_connections', 'allowed_origins',
                ],
            ]);

        $data = $response->json('data');
        expect($data['name'])->toBe('My Publisher');
        expect($data['app_id'])->toStartWith('app-');
        expect($data['app_key'])->toStartWith('key-');
        expect($data['app_secret'])->toStartWith('secret-');
        expect($data['is_active'])->toBeTrue();

        $this->assertDatabaseHas('reverb_apps', ['name' => 'My Publisher']);
    });

    it('creates a reverb app with custom credentials', function () {
        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', [
                'name' => 'Custom App',
                'app_id' => 'my-custom-id',
                'app_key' => 'my-custom-key',
                'app_secret' => 'my-custom-secret',
                'allowed_origins' => ['https://example.com'],
                'max_connections' => 500,
            ]);

        $response->assertCreated();

        $data = $response->json('data');
        expect($data['app_id'])->toBe('my-custom-id');
        expect($data['app_key'])->toBe('my-custom-key');
        expect($data['app_secret'])->toBe('my-custom-secret');
        expect($data['allowed_origins'])->toBe(['https://example.com']);
        expect($data['max_connections'])->toBe(500);
    });

    it('validates name is required', function () {
        $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates app_id uniqueness', function () {
        ReverbApp::factory()->create(['app_id' => 'existing-id']);

        $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', [
                'name' => 'Test',
                'app_id' => 'existing-id',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['app_id']);
    });

    it('validates app_key uniqueness', function () {
        ReverbApp::factory()->create(['app_key' => 'existing-key']);

        $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps', [
                'name' => 'Test',
                'app_key' => 'existing-key',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['app_key']);
    });
});

describe('GET /api/reverb-apps/{reverbApp}', function () {
    it('returns a reverb app by id', function () {
        $app = ReverbApp::factory()->create(['name' => 'Lookup Test']);

        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->getJson("/api/reverb-apps/{$app->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Lookup Test')
            ->assertJsonPath('data.app_secret', $app->app_secret);
    });

    it('returns 404 for non-existent app', function () {
        $this->withHeader('Authorization', 'Bearer test-api-token')
            ->getJson('/api/reverb-apps/99999')
            ->assertNotFound();
    });
});

describe('GET /api/reverb-apps (index)', function () {
    it('lists all reverb apps', function () {
        ReverbApp::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->getJson('/api/reverb-apps');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter by active status', function () {
        ReverbApp::factory()->count(2)->create(['is_active' => true]);
        ReverbApp::factory()->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->getJson('/api/reverb-apps?active=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /api/reverb-apps/restart', function () {
    it('dispatches a restart job', function () {
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer test-api-token')
            ->postJson('/api/reverb-apps/restart');

        $response->assertOk()
            ->assertJsonPath('message', 'Reverb server restart has been queued.');

        Queue::assertPushed(\App\Jobs\RestartReverbServer::class);
    });
});
