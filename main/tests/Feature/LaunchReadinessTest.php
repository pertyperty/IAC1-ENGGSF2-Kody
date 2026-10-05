<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('launch configuration stays fail closed without staging evidence and prints no secrets or provider calls', function () {
    Http::preventStrayRequests();
    config(['operations.staging_verified' => false, 'xendit.secret_key' => 'PRIVATE_TEST_SECRET']);
    $this->withoutMockingConsoleOutput();
    expect(Artisan::call('kody:launch-check'))->toBe(1);
    expect(Artisan::output())->toContain('Staging evidence attested', 'MISSING')->not->toContain('PRIVATE_TEST_SECRET');
    Http::assertNothingSent();
});

test('launch checks accept attested secure configuration with disabled payments and still distinguish it from live proof', function () {
    Http::preventStrayRequests();
    config(['app.debug' => false, 'app.url' => 'https://kody.example.test', 'session.secure' => true,
        'session.http_only' => true, 'session.same_site' => 'lax', 'queue.default' => 'database',
        'queue.connections.database.connection' => config('database.default'), 'queue.connections.database.retry_after' => 90,
        'operations.operations_owner' => 'Primary', 'operations.backup_responder' => 'Backup',
        'operations.budget_enforced' => true, 'operations.staging_verified' => true,
        'account.credentials.disk' => 'local', 'filesystems.disks.local.visibility' => 'private', 'xendit.enabled' => false]);
    $this->withoutMockingConsoleOutput();
    expect(Artisan::call('kody:launch-check'))->toBe(0);
    expect(Artisan::output())->toContain('Passing configuration does not prove live integrations');
    expect(Artisan::call('kody:ledger-check'))->toBe(0);
    Http::assertNothingSent();
});

test('ledger reconciliation detects a drifted wallet and leaves its accounting history unchanged', function () {
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 100, 10000);
    DB::table('wallet_accounts')->where('user_id', $user->id)->update(['balance' => 99]);
    $this->withoutMockingConsoleOutput();
    expect(Artisan::call('kody:ledger-check'))->toBe(1);
    expect(DB::table('wallet_accounts')->where('user_id', $user->id)->value('balance'))->toBe(99);
    $this->assertDatabaseCount('wallet_operations', 1);
});
