<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * An install that predates the encrypted-secret migrations has app_secret as
 * VARCHAR(255) and its secrets in plaintext. A 36- or 39-character secret
 * encrypts to 256 characters, one more than that column holds, so the column
 * must be TEXT before the first secret is encrypted (#7).
 *
 * RefreshDatabase has already run every migration, so each test rebuilds
 * reverb_apps exactly as the original create migration left it, then runs
 * the encryption migration on its own, the way `migrate` reaches it on an
 * install upgraded from before March 2026.
 *
 * SQLite ignores VARCHAR lengths, so under the default test connection the
 * overflow itself cannot happen and the column type is the signal. Run this
 * file with DB_CONNECTION=mysql to see the overflow too: before the fix the
 * migration throws "Data too long for column 'app_secret'".
 */

function migration(string $name): object
{
    return require database_path("migrations/{$name}.php");
}

beforeEach(function () {
    $create = migration('2025_07_19_000003_create_reverb_apps_table');
    $create->down();
    $create->up();
});

function insertPlaintextApp(string $appId, string $secret): int
{
    return DB::table('reverb_apps')->insertGetId([
        'name' => $appId,
        'app_id' => $appId,
        'app_key' => "{$appId}-key",
        'app_secret' => $secret,
        'is_active' => true,
        'allowed_origins' => json_encode(['*']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('the column starts as the original VARCHAR', function () {
    expect(Schema::getColumnType('reverb_apps', 'app_secret'))->not->toBe('text');
});

test('the encryption migration widens app_secret before encrypting', function () {
    insertPlaintextApp('demo', str_repeat('s', 36));

    migration('2026_03_09_000000_encrypt_reverb_app_secrets')->up();

    expect(Schema::getColumnType('reverb_apps', 'app_secret'))->toBe('text');
});

test('secrets whose ciphertext exceeds 255 characters survive encryption intact', function () {
    // 36 and 39 are the lengths production holds; both encrypt to 256 characters.
    $secrets = [
        'short' => str_repeat('a', 20),
        'uuid-length' => str_repeat('b', 36),
        'longer' => str_repeat('c', 39),
    ];

    $ids = [];
    foreach ($secrets as $appId => $secret) {
        $ids[$appId] = insertPlaintextApp($appId, $secret);
    }

    migration('2026_03_09_000000_encrypt_reverb_app_secrets')->up();

    foreach ($secrets as $appId => $secret) {
        $stored = DB::table('reverb_apps')->where('id', $ids[$appId])->value('app_secret');

        expect(strlen($stored))->toBeGreaterThan(strlen($secret))
            ->and(Crypt::decryptString($stored))->toBe($secret);
    }
});

test('the later widen migration still runs cleanly after the encryption migration', function () {
    insertPlaintextApp('demo', str_repeat('s', 39));

    migration('2026_03_09_000000_encrypt_reverb_app_secrets')->up();
    migration('2026_05_20_100030_widen_app_secret_column_on_reverb_apps_table')->up();

    $stored = DB::table('reverb_apps')->where('app_id', 'demo')->value('app_secret');

    expect(Schema::getColumnType('reverb_apps', 'app_secret'))->toBe('text')
        ->and(Crypt::decryptString($stored))->toBe(str_repeat('s', 39));
});
