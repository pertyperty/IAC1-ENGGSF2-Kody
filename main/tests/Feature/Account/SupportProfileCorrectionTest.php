<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendSupportCorrectionNotice;
use App\Mail\Account\SupportCorrectionNotice;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\SecureAccountMailer;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\SupportProfileCorrections;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function supportCorrectionData(User $target, array $overrides = []): array
{
    return array_replace(['username' => 'correctedplayer', 'first_name' => 'Renée', 'last_name' => 'Coder',
        'profile_version' => $target->fresh()->profile_version, 'current_password' => 'password',
        'support_requested' => true, 'confirmed' => true], $overrides);
}

test('G02 support Admin corrects approved fields across target roles without changing identity security or ownership', function (Role $role) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount($role);
    $original = $target->only(['email', 'email_verified_at', 'password', 'account_role', 'account_status', 'moderator_prior_role']);
    moduleSignIn($this, $admin);
    $this->get(route('account-governance.show', $target))->assertOk()->assertSee('Help with profile details')->assertDontSee($target->email);
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertRedirect(route('account-governance.show', $target));
    expect($target->fresh()->username)->toBe('correctedplayer')->and($target->fresh()->name)->toBe('Renée Coder')
        ->and($target->fresh()->profile_version)->toBe(2)->and($target->fresh()->active_session_hash)->toBeNull()
        ->and($target->fresh()->only(array_keys($original)))->toEqual($original);
    $correction = DB::table('account_support_corrections')->sole();
    expect(json_decode($correction->fields, true))->toBe(['username', 'first_name', 'last_name']);
    $audit = DB::table('audit_events')->where('event', 'account.support-corrected')->sole();
    expect($audit->context)->not->toContain('Renée', 'correctedplayer', $target->email, 'password');
    expect(DB::table('jobs')->sole()->payload)->not->toContain('Renée', 'correctedplayer', $target->email, 'current_password');
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G02 support non Admin actors cannot view personal correction fields or change another profile', function (Role $role) {
    $actor = moduleAccount($role);
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, $actor);
    $this->get(route('account-governance.show', $target))->assertStatus($role === Role::Moderator ? 200 : 403)->assertDontSee('Help with profile details');
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertForbidden();
    expect(fn () => app(SupportProfileCorrections::class)->correct($actor, 'module-test-session', $target, supportCorrectionData($target)))->toThrow(AuthorizationException::class);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G02 support protects self Administrator and restricted targets', function () {
    // Exercise every target rule independently of the separately tested request budget.
    $this->withoutMiddleware(ThrottleRequests::class);
    $admin = moduleAccount(Role::Administrator);
    $targets = [$admin, moduleAccount(Role::Administrator)];
    foreach ([AccountStatus::Suspended, AccountStatus::Archived, AccountStatus::Unverified, AccountStatus::Deleted] as $status) {
        $target = moduleAccount(Role::Learner);
        $target->forceFill(['account_status' => $status])->save();
        $targets[] = $target;
    }
    moduleSignIn($this, $admin);
    foreach ($targets as $target) {
        $this->get(route('account-governance.show', $target))->assertOk()->assertDontSee('Help with profile details');
        $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertForbidden();
    }
    $admin->forceFill(['active_session_expires_at' => now()->subSecond()])->save();
    $target = moduleAccount(Role::Learner);
    expect(fn () => app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target)))->toThrow(AuthorizationException::class);
});

test('G02 support validates confirmation password stale version and profile constraints', function (array $overrides, string $field) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, $admin);
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target, $overrides))->assertSessionHasErrors($field);
    expect($target->fresh()->profile_version)->toBe(1)->and(session()->getOldInput('current_password'))->toBeNull();
    $this->assertDatabaseCount('account_support_corrections', 0);
})->with([
    [['confirmed' => false], 'confirmed'], [['support_requested' => false], 'support_requested'],
    [['current_password' => 'wrong'], 'current_password'], [['profile_version' => 100], 'account'],
    [['username' => 'short'], 'username'], [['username' => str_repeat('x', 31)], 'username'],
    [['first_name' => 'Invalid1'], 'first_name'], [['last_name' => str_repeat('é', 51)], 'last_name'],
    [['email' => 'other@example.test'], 'email'], [['password' => 'NewPassword12!'], 'password'],
    [['name' => 'Forged'], 'name'], [['account_role' => 'Admin'], 'account_role'], [['permissions' => ['all']], 'permissions'],
    [['account_status' => 'Active'], 'account_status'], [['email_verified_at' => '2026-10-03'], 'email_verified_at'],
    [['user_id' => 100], 'user_id'], [['moderator_prior_role' => 'Instructor'], 'moderator_prior_role'],
]);

test('G02 support unchanged submissions preserve sessions and duplicate usernames produce a safe validation error', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $service = app(SupportProfileCorrections::class);
    expect($service->correct($admin, 'module-test-session', $target, supportCorrectionData($target, $target->only(['username', 'first_name', 'last_name']))))->toBeFalse();
    expect($target->fresh()->profile_version)->toBe(1)->and($target->fresh()->active_session_hash)->not->toBeNull();
    $other = moduleAccount(Role::Learner);
    expect(fn () => $service->correct($admin, 'module-test-session', $target, supportCorrectionData($target, ['username' => $other->username])))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('account_support_corrections', 0);
});

test('G02 support audit and queue failures roll back profile sessions and correction history', function (bool $queueFailure) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $original = $target->fresh()->only(['name', 'username', 'active_session_hash', 'profile_version']);
    if ($queueFailure) {
        config(['queue.connections.database.connection' => 'other_database']);
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    }
    expect(fn () => app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target)))->toThrow($queueFailure ? LogicException::class : RuntimeException::class);
    expect($target->fresh()->only(array_keys($original)))->toEqual($original);
    $this->assertDatabaseCount('account_support_corrections', 0);
})->with([false, true]);

test('G02 support notices retry sanitized delivery failures and skip acknowledged notices', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target));
    $id = DB::table('account_support_corrections')->sole()->id;
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->andThrow(new RuntimeException('private transport details'));
    expect(fn () => (new SendSupportCorrectionNotice($id))->handle())->toThrow(RuntimeException::class, 'Support correction notice could not be delivered.');
    expect(DB::table('account_support_corrections')->sole()->failed_at)->not->toBeNull()->and($target->fresh()->username)->toBe('correctedplayer');
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->with('notifications', $target->email, Mockery::type(SupportCorrectionNotice::class));
    (new SendSupportCorrectionNotice($id))->handle();
    (new SendSupportCorrectionNotice($id))->handle();
    expect(DB::table('account_support_corrections')->sole()->sent_at)->not->toBeNull();
    $html = (new SupportCorrectionNotice(['username', 'first_name'], now()->toIso8601String()))->render();
    expect($html)->toContain('username, first name', 'Asia/Manila')->not->toContain($target->email, 'Renée');
});

test('G02 support history survives deletion without personal values and queued notice cancels', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target));
    $target->refresh()->forceFill(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()])->save();
    app(AccountDeletion::class)->delete($target, 'module-test-session', deletionData($target));
    $this->mock(SecureAccountMailer::class)->shouldNotReceive('send');
    (new SendSupportCorrectionNotice(DB::table('account_support_corrections')->sole()->id))->handle();
    expect(DB::table('account_support_corrections')->sole()->cancelled_at)->not->toBeNull();
});

test('G02 support PostgreSQL constraints and guarded migration preserve valid durable history', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target));
    foreach (['["email"]', '[]', '{}'] as $invalid) {
        expect(fn () => DB::transaction(fn () => DB::table('account_support_corrections')->update(['fields' => $invalid])))->toThrow(QueryException::class);
    }
    expect(fn () => DB::transaction(fn () => DB::table('account_support_corrections')->update(['actor_id' => $target->id])))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_10_03_000020_create_account_support_corrections.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});

test('G02 support routes enforce authentication CSRF and missing targets', function () {
    $target = moduleAccount(Role::Learner);
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->patch(route('account-governance.profile', 999999), supportCorrectionData($target))->assertNotFound();
    $this->app->detectEnvironment(fn () => 'local');
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertStatus(419);
});

test('G02 support correction password confirmations are rate limited', function () {
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->patch(route('account-governance.profile', $target), supportCorrectionData($target, ['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
    }
    $this->patch(route('account-governance.profile', $target), supportCorrectionData($target))->assertStatus(429);
    $this->assertDatabaseCount('account_support_corrections', 0);
});
