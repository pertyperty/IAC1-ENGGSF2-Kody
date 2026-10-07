<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->originalStorage = storage_path();
    $this->adminStorage = storage_path('framework/testing/admin-'.Str::uuid());
    app()->useStoragePath($this->adminStorage);
});

afterEach(function () {
    app()->useStoragePath($this->originalStorage);
    File::deleteDirectory($this->adminStorage);
});

test('local console creates a hashed Active Administrator and secret-free audit', function () {
    $this->artisan('kody:admin-create-local', ['email' => ' ADMIN@Example.test ', '--generate' => true])->assertSuccessful();
    $user = User::sole();
    $credential = file_get_contents(storage_path('app/private/local-admin-credentials.txt'));
    preg_match('/Password: (.+)/', $credential, $matches);
    expect($user->email)->toBe('admin@example.test')
        ->and($user->account_role)->toBe(Role::Administrator)
        ->and($user->account_status)->toBe(AccountStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check($matches[1], $user->password))->toBeTrue()
        ->and($user->active_session_hash)->toBeNull();
    $this->assertDatabaseHas('audit_events', ['event' => 'local_administrator_created', 'actor_id' => null, 'subject_user_id' => $user->id]);
    $audit = DB::table('audit_events')->sole();
    expect($audit->context)->not->toContain($matches[1])->not->toContain('password');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => $matches[1]])->assertRedirect(route('dashboard'));
    $this->withCredentials()->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('account-governance.index'))->assertOk();
});

test('administrator provisioning cannot run in production or against a remote database', function (string $setting) {
    if ($setting === 'production') {
        app()['env'] = 'production';
    } else {
        config(['database.connections.remote_console_test' => array_replace(config('database.connections.pgsql'), ['host' => 'database.example.test', 'url' => null]),
            'database.default' => 'remote_console_test']);
    }
    $this->artisan('kody:admin-create-local', ['email' => 'admin@example.test', '--generate' => true])->assertFailed();
    expect(File::exists(storage_path('app/private/local-admin-credentials.txt')))->toBeFalse();
    // Restore the connection before RefreshDatabase rolls back the fixture transaction.
    if ($setting !== 'production') {
        config(['database.default' => 'pgsql']);
    }
})->with(['production', 'remote']);

test('duplicate identities and a pre-existing credential file never overwrite users or secrets', function (string $conflict) {
    $existing = User::factory()->create(['email' => 'existing@example.test', 'username' => 'existing_player']);
    $before = $existing->fresh()->getRawOriginal();
    if ($conflict === 'file') {
        File::ensureDirectoryExists(storage_path('app/private'));
        File::put(storage_path('app/private/local-admin-credentials.txt'), 'keep this file');
    }
    $this->artisan('kody:admin-create-local', ['email' => $conflict === 'email' ? $existing->email : 'admin@example.test',
        '--username' => $conflict === 'username' ? $existing->username : 'kody_admin', '--generate' => true])->assertFailed();
    expect(User::count())->toBe(1)->and($existing->fresh()->getRawOriginal())->toBe($before);
    $this->assertDatabaseCount('audit_events', 0);
    if ($conflict === 'file') {
        expect(File::get(storage_path('app/private/local-admin-credentials.txt')))->toBe('keep this file');
    }
})->with(['email', 'username', 'file']);

test('hidden console passwords follow existing account validation', function () {
    $this->artisan('kody:admin-create-local', ['email' => 'admin@example.test'])
        ->expectsQuestion('Password (12–32 characters, uppercase, lowercase, number and symbol)', 'weak')
        ->expectsQuestion('Confirm password', 'weak')->assertFailed();
    $this->assertDatabaseCount('users', 0);
});

test('an audit failure rolls back administrator creation', function () {
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('synthetic failure'));
    $this->artisan('kody:admin-create-local', ['email' => 'admin@example.test', '--generate' => true])->assertFailed();
    $this->assertDatabaseCount('users', 0);
    expect(File::exists(storage_path('app/private/local-admin-credentials.txt')))->toBeFalse();
});
