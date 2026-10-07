<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LocalRoleAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->originalStorage = storage_path();
    $this->roleStorage = storage_path('framework/testing/roles-'.Str::uuid());
    app()->useStoragePath($this->roleStorage);
});

afterEach(function () {
    app()->useStoragePath($this->originalStorage);
    File::deleteDirectory($this->roleStorage);
});

test('local default seeds create verified Active accounts for every role with private random passwords', function () {
    app()['env'] = 'local';
    $this->seed(DatabaseSeeder::class);
    $files = File::glob(storage_path('app/private/local-role-credentials-*.txt'));
    expect($files)->toHaveCount(1)->and(User::count())->toBe(5);
    $credentials = File::get($files[0]);
    preg_match_all('/Email: (.+)\nUsername: (.+)\nPassword: (.+)/', $credentials, $matches, PREG_SET_ORDER);
    expect($matches)->toHaveCount(5)->and(array_unique(array_column($matches, 3)))->toHaveCount(5);
    foreach ($matches as [, $email, $username, $password]) {
        $user = User::where('email', $email)->sole();
        expect($user->username)->toBe($username)->and($user->account_status)->toBe(AccountStatus::Active)
            ->and($user->email_verified_at)->not->toBeNull()->and(strlen($password))->toBe(30)
            ->and(Hash::check($password, $user->password))->toBeTrue();
        $this->assertDatabaseHas('audit_events', ['event' => 'local_role_account_created', 'subject_user_id' => $user->id]);
        expect(DB::table('audit_events')->where('subject_user_id', $user->id)->sole()->context)->not->toContain($password);
    }
    expect(User::pluck('account_role')->map(fn ($role) => $role->value)->sort()->values()->all())
        ->toBe(collect(Role::cases())->map(fn ($role) => $role->value)->sort()->values()->all());
    $this->assertDatabaseCount('audit_events', 5);
});

test('role seeder reruns preserve existing passwords status verification sessions and audits', function () {
    $this->seed(LocalRoleAccountsSeeder::class);
    User::where('email', 'learner@kody.local')->update(['account_status' => AccountStatus::Suspended->value]);
    $before = User::orderBy('id')->get()->map->getRawOriginal()->all();
    $files = File::glob(storage_path('app/private/local-role-credentials-*.txt'));
    $this->seed(LocalRoleAccountsSeeder::class);
    expect(User::orderBy('id')->get()->map->getRawOriginal()->all())->toBe($before)
        ->and(File::glob(storage_path('app/private/local-role-credentials-*.txt')))->toBe($files);
    $this->assertDatabaseCount('audit_events', 5);
});

test('partial fixtures add only missing identities and leave the original Administrator intact', function () {
    $admin = User::factory()->create(['email' => 'admin@kody.local', 'username' => 'kody_admin', 'account_role' => Role::Administrator]);
    $learner = User::factory()->create(['email' => 'learner@kody.local', 'username' => 'kody_learner']);
    $beforeAdmin = $admin->fresh()->getRawOriginal();
    $beforeLearner = $learner->fresh()->getRawOriginal();
    $this->seed(LocalRoleAccountsSeeder::class);
    expect(User::count())->toBe(6)->and($admin->fresh()->getRawOriginal())->toBe($beforeAdmin)
        ->and($learner->fresh()->getRawOriginal())->toBe($beforeLearner);
    $credentials = File::get(File::glob(storage_path('app/private/local-role-credentials-*.txt'))[0]);
    expect($credentials)->not->toContain('Email: learner@kody.local')->not->toContain('Email: admin@kody.local');
    $this->assertDatabaseCount('audit_events', 4);
});

test('default testing seeds remain empty unless the role fixture seeder is explicitly requested', function () {
    $this->seed(DatabaseSeeder::class);
    $this->assertDatabaseCount('users', 0);
    expect(File::exists(storage_path('app/private')))->toBeFalse();
});

test('default seeders leave nonlocal databases empty and explicit role seeds refuse deployment environments', function (string $environment) {
    app()['env'] = $environment;
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
    $this->assertDatabaseCount('users', 0);
    expect(fn () => $this->artisan('db:seed', ['--class' => LocalRoleAccountsSeeder::class, '--force' => true])->run())
        ->toThrow(RuntimeException::class, 'require local/testing');
    $this->assertDatabaseCount('users', 0);
    expect(File::exists(storage_path('app/private')))->toBeFalse();
})->with(['production', 'staging']);

test('explicit role seeder rejects a remote database before provisioning', function () {
    config(['database.connections.remote_seed_test' => array_replace(config('database.connections.pgsql'),
        ['host' => 'database.example.test', 'url' => null]), 'database.default' => 'remote_seed_test']);
    try {
        expect(fn () => $this->seed(LocalRoleAccountsSeeder::class))->toThrow(RuntimeException::class, 'loopback PostgreSQL');
    } finally {
        config(['database.default' => 'pgsql']);
    }
    $this->assertDatabaseCount('users', 0);
});

test('conflicting identities rollback the entire role batch without promoting an existing user', function (string $conflict) {
    $existing = User::factory()->create([
        'email' => $conflict === 'email' ? 'instructor@kody.local' : 'original@example.test',
        'username' => $conflict === 'username' ? 'kody_instructor' : 'original_player',
    ]);
    $before = $existing->fresh()->getRawOriginal();
    expect(fn () => $this->seed(LocalRoleAccountsSeeder::class))->toThrow(RuntimeException::class, 'no accounts changed');
    expect(User::count())->toBe(1)->and($existing->fresh()->getRawOriginal())->toBe($before);
    $this->assertDatabaseCount('audit_events', 0);
    expect(File::exists(storage_path('app/private')))->toBeFalse();
})->with(['email', 'username']);

test('audit or private storage failure rolls back all seeded accounts', function (string $failure) {
    if ($failure === 'audit') {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('synthetic failure'));
    } else {
        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/private'), 'preserve this existing file');
    }
    expect(fn () => $this->seed(LocalRoleAccountsSeeder::class))->toThrow(RuntimeException::class, 'no accounts changed');
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('audit_events', 0);
    if ($failure === 'storage') {
        expect(File::get(storage_path('app/private')))->toBe('preserve this existing file');
    }
})->with(['audit', 'storage']);
