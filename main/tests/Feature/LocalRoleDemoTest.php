<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\ContributorEligibility;
use Database\Seeders\LocalRoleAccountsSeeder;
use Database\Seeders\LocalRoleApplicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->originalStorage = storage_path();
    $this->demoStorage = storage_path('framework/testing/role-demo-'.Str::uuid());
    app()->useStoragePath($this->demoStorage);
});
afterEach(function () {
    app()->useStoragePath($this->originalStorage);
    File::deleteDirectory($this->demoStorage);
});

test('local role demo prepares both learners without approving applications granting rewards or replacing passwords', function () {
    $this->seed(LocalRoleAccountsSeeder::class);
    $four = User::where('email', 'learner4@kody.local')->sole();
    $five = User::where('email', 'learner5@kody.local')->sole();
    $passwords = [$four->password, $five->password];
    $creators = User::whereIn('account_role', [Role::Instructor, Role::Contributor, Role::Moderator])->orderBy('id')->get()->map->getRawOriginal()->all();
    $five->forceFill(['account_status' => AccountStatus::Suspended])->save();
    expect(fn () => app(LocalRoleApplicationDemoSeeder::class)->prepare())->toThrow(RuntimeException::class, 'inactive');
    $this->assertDatabaseCount('learning_activity_days', 0);
    app(LocalRoleApplicationDemoSeeder::class)->prepare(true);
    foreach ([$four, $five] as $user) {
        expect(app(ContributorEligibility::class)->snapshot($user->fresh()))->toMatchArray(['eligible' => true, 'completed_modules_count' => 25, 'completed_challenges_count' => 50]);
        expect($user->fresh()->account_role)->toBe(Role::Learner)->and($user->fresh()->account_status)->toBe(AccountStatus::Active);
    }
    expect([$four->fresh()->password, $five->fresh()->password])->toBe($passwords);
    expect(User::whereIn('account_role', [Role::Instructor, Role::Contributor, Role::Moderator])->orderBy('id')->get()->map->getRawOriginal()->all())->toBe($creators);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('wallet_entries', 0);
    $this->assertDatabaseCount('contributor_applications', 0);
    $this->assertDatabaseCount('instructor_applications', 0);
    $this->assertDatabaseHas('account_enforcements', ['user_id' => $five->id, 'action' => 'Reinstated']);
    expect(DB::table('learning_modules')->where('status', 'Published')->count())->toBe(0);
    expect(DB::table('coding_challenges')->where('status', 'Published')->count())->toBe(0);
    app(LocalRoleApplicationDemoSeeder::class)->prepare();
    $this->assertDatabaseCount('learning_modules', 25);
    $this->assertDatabaseCount('coding_challenges', 50);
    $this->assertDatabaseCount('learning_activity_days', 50);
    $this->assertDatabaseCount('challenge_submissions', 100);
    expect(DB::table('account_enforcements')->where('user_id', $five->id)->count())->toBe(1);
});

test('role demo refuses production remote connections and unaudited lookalike identities', function () {
    app()['env'] = 'production';
    expect(fn () => app(LocalRoleApplicationDemoSeeder::class)->prepare(true))->toThrow(RuntimeException::class, 'local/testing');
    app()['env'] = 'testing';
    config(['database.connections.demo_remote' => array_replace(config('database.connections.pgsql'), ['host' => 'db.example.test', 'url' => null]), 'database.default' => 'demo_remote']);
    try {
        expect(fn () => app(LocalRoleApplicationDemoSeeder::class)->prepare(true))->toThrow(RuntimeException::class, 'loopback');
    } finally {
        config(['database.default' => 'pgsql']);
    }
    User::factory()->create(['email' => 'learner4@kody.local']);
    expect(fn () => app(LocalRoleApplicationDemoSeeder::class)->prepare(true))->toThrow(RuntimeException::class, 'audited local');
    $this->assertDatabaseCount('learning_activity_days', 0);
});
