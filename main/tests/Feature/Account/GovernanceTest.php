<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendEnforcementNotice;
use App\Mail\Account\EnforcementNotice;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\SecureAccountMailer;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function enforcementData(User $target, array $overrides = []): array
{
    return array_replace(['action' => 'Suspended', 'profile_version' => $target->fresh()->profile_version, 'confirmed' => true], $overrides);
}

test('G01 staff browse current account metadata with literal search filters pagination and interruption retention', function (Role $role) {
    $actor = moduleAccount($role);
    $target = User::factory()->create(['username' => 'quest_player', 'first_name' => 'PrivateLegalFirst', 'name' => 'Private Legal Name']);
    moduleSignIn($this, $actor);
    $this->get(route('account-governance.index', ['q' => 'quest_', 'role' => 'Learner', 'status' => 'Active']))->assertOk()->assertSee('quest_player')
        ->assertDontSee($target->email)->assertDontSee('PrivateLegalFirst')->assertDontSee('Private Legal Name')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('account-governance.index'))->assertSee('quest_player')->assertSee('quest_', false);
    $this->get(route('account-governance.index', ['q' => '%']))->assertDontSee('quest_player');
    $this->get(route('account-governance.show', $target))->assertOk()->assertSee('Confirm suspension')->assertDontSee($target->email);
    $this->getJson(route('account-governance.index', ['role' => 'root']))->assertUnprocessable();
    $this->getJson(route('account-governance.index', ['q' => str_repeat('x', 81)]))->assertUnprocessable();
})->with([Role::Moderator, Role::Administrator]);

test('G03 G04 approved participant targets suspend immediately and reinstate without resurrecting sessions', function (Role $role) {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount($role);
    $oldEmail = $target->email;
    app(AccountRecoveryService::class)->request($target);
    $service = app(AccountEnforcement::class);
    $service->change($actor, 'module-test-session', $target, enforcementData($target));
    expect($target->fresh()->account_status)->toBe(AccountStatus::Suspended)->and($target->fresh()->active_session_hash)->toBeNull()
        ->and($target->fresh()->account_role)->toBe($role)->and($target->fresh()->email)->toBe($oldEmail);
    expect(DB::table('account_recoveries')->sole()->token_hash)->toBeNull();
    $notice = DB::table('account_enforcements')->sole();
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->with('notifications', $oldEmail, Mockery::type(EnforcementNotice::class));
    (new SendEnforcementNotice($notice->id))->handle();
    (new SendEnforcementNotice($notice->id))->handle();
    $service->change($actor, 'module-test-session', $target, enforcementData($target, ['action' => 'Reinstated']));
    expect($target->fresh()->account_status)->toBe(AccountStatus::Active)->and($target->fresh()->active_session_hash)->toBeNull()
        ->and($target->fresh()->profile_version)->toBe(3);
    expect(DB::table('audit_events')->whereIn('event', ['account.suspended', 'account.reinstated'])->count())->toBe(2);
    expect(DB::table('account_enforcements')->count())->toBe(2);
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('G03 G04 enforce staff hierarchy self protection and Administrator protection', function (Role $actorRole, Role $targetRole, bool $allowed) {
    $actor = moduleAccount($actorRole);
    $target = moduleAccount($targetRole);
    if ($allowed) {
        app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target));
        expect($target->fresh()->account_status)->toBe(AccountStatus::Suspended);
    } else {
        expect(fn () => app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target)))->toThrow(AuthorizationException::class);
        expect($target->fresh()->account_status)->toBe(AccountStatus::Active);
    }
})->with([
    [Role::Administrator, Role::Moderator, true], [Role::Moderator, Role::Moderator, false], [Role::Moderator, Role::Administrator, false],
    [Role::Administrator, Role::Administrator, false], [Role::Learner, Role::Learner, false], [Role::Contributor, Role::Learner, false], [Role::Instructor, Role::Learner, false],
]);

test('G03 self enforcement expired and restricted staff sessions cannot mutate accounts', function () {
    $actor = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $service = app(AccountEnforcement::class);
    expect(fn () => $service->change($actor, 'module-test-session', $actor, enforcementData($actor)))->toThrow(AuthorizationException::class);
    $actor->forceFill(['active_session_expires_at' => now()->subSecond()])->save();
    expect(fn () => $service->change($actor, 'module-test-session', $target, enforcementData($target)))->toThrow(AuthorizationException::class);
    $actor->forceFill(['account_status' => AccountStatus::Suspended, 'active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => $service->change($actor, 'module-test-session', $target, enforcementData($target)))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('account_enforcements', 0);
});

test('G03 G04 stale forms invalid transitions and protected fields fail without mutation', function () {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, $actor);
    foreach ([['profile_version' => 99], ['action' => 'Reinstated'], ['confirmed' => false], ['account_role' => 'Admin'], ['action' => 'Deleted']] as $override) {
        $this->post(route('account-governance.enforce', $target), enforcementData($target, $override))->assertSessionHasErrors();
    }
    expect($target->fresh()->account_status)->toBe(AccountStatus::Active);
    $this->post(route('account-governance.enforce', $target), enforcementData($target))->assertRedirect(route('account-governance.show', $target));
    $this->post(route('account-governance.enforce', $target), enforcementData($target))->assertSessionHasErrors('account');
    $this->get(route('account-governance.show', $target))->assertSee('Confirm reinstatement');
});

test('G03 cannot turn Archived Unverified or Deleted accounts into suspended accounts', function (AccountStatus $status) {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount(Role::Learner);
    $target->forceFill(['account_status' => $status])->save();
    expect(fn () => app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target)))->toThrow(ValidationException::class);
    expect($target->fresh()->account_status)->toBe($status);
})->with([AccountStatus::Archived, AccountStatus::Unverified, AccountStatus::Deleted]);

test('G03 audit or database queue failure rolls back status security proofs and enforcement records', function (bool $queueFailure) {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount(Role::Learner);
    $original = $target->active_session_hash;
    if ($queueFailure) {
        config(['queue.connections.database.connection' => 'other_database']);
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    }
    expect(fn () => app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target)))->toThrow($queueFailure ? LogicException::class : RuntimeException::class);
    expect($target->fresh()->account_status)->toBe(AccountStatus::Active)->and($target->fresh()->active_session_hash)->toBe($original);
    $this->assertDatabaseCount('account_enforcements', 0);
})->with([false, true]);

test('G03 queued notice failure does not reverse suspension and retries a durable delivery', function () {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount(Role::Learner);
    app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target));
    $notice = DB::table('account_enforcements')->sole();
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->andThrow(new RuntimeException('private transport failure'));
    expect(fn () => (new SendEnforcementNotice($notice->id))->handle())->toThrow(RuntimeException::class, 'Account enforcement notice could not be delivered.');
    expect($target->fresh()->account_status)->toBe(AccountStatus::Suspended)->and(DB::table('account_enforcements')->sole()->failed_at)->not->toBeNull();
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once();
    (new SendEnforcementNotice($notice->id))->handle();
    (new SendEnforcementNotice($notice->id))->handle();
    expect(DB::table('account_enforcements')->sole()->sent_at)->not->toBeNull();
});

test('G03 committed evaluations finish during suspension while new attempts stay blocked', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $target = moduleAccount(Role::Learner);
    $attempt = submitAttempt($challenge, $target);
    $actor = moduleAccount(Role::Moderator);
    app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target));
    expect(fn () => submitAttempt($challenge, $target))->toThrow(AuthorizationException::class);
    evaluateAttempt($attempt);
    expect($attempt->fresh()->status)->toBe('Passed')->and($target->fresh()->account_status)->toBe(AccountStatus::Suspended);
});

test('G03 G04 history survives approved deletion while pending notices cannot disclose to deleted identities', function () {
    $actor = moduleAccount(Role::Moderator);
    $target = moduleAccount(Role::Learner);
    $service = app(AccountEnforcement::class);
    $service->change($actor, 'module-test-session', $target, enforcementData($target));
    $service->change($actor, 'module-test-session', $target, enforcementData($target, ['action' => 'Reinstated']));
    $target->refresh()->forceFill(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()])->save();
    app(AccountDeletion::class)->delete($target, 'module-test-session', deletionData($target));
    $this->mock(SecureAccountMailer::class)->shouldNotReceive('send');
    foreach (DB::table('account_enforcements')->get() as $notice) {
        (new SendEnforcementNotice($notice->id))->handle();
    }
    expect(DB::table('account_enforcements')->whereNotNull('cancelled_at')->count())->toBe(2);
    expect(fn () => $service->change($actor, 'module-test-session', $target, enforcementData($target, ['action' => 'Reinstated'])))->toThrow(AuthorizationException::class);
});

test('G01 G03 require authentication authorization missing-record handling and CSRF', function () {
    $this->get(route('account-governance.index'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create());
    $this->get(route('account-governance.index'))->assertForbidden();
    $actor = moduleAccount(Role::Moderator);
    moduleSignIn($this, $actor);
    $this->get(route('account-governance.show', 999999))->assertNotFound();
    $this->app->detectEnvironment(fn () => 'local');
    $target = moduleAccount(Role::Learner);
    $this->post(route('account-governance.enforce', $target), enforcementData($target))->assertStatus(419);
});
