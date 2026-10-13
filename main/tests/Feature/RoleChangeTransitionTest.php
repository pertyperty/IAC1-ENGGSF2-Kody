<?php

use App\Actions\Account\ReviewInstructorApplication;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\ContributorApplications;
use App\Services\Account\RoleChangeNotices;
use App\Services\Administration\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('approved Instructor application produces a durable mascot transition without needing an email worker', function () {
    $learner = moduleAccount(Role::Learner);
    $application = pendingInstructor($learner);
    $reviewer = moduleAccount(Role::Moderator);
    app(ReviewInstructorApplication::class)->handle($reviewer, 'module-test-session', $application, 1, 'Approved', null, true);
    expect($learner->fresh()->account_role)->toBe(Role::Instructor);
    expect($learner->notifications()->where('type', RoleChangeNotices::TYPE)->count())->toBe(1);
    expect(app(RoleChangeNotices::class)->snapshot($learner, 'module-test-session')['update'])->toMatchArray(['role' => 'Instructor', 'previous' => 'Learner', 'workspace' => route('studio.index')]);
    expect(DB::table('creator_decision_deliveries')->sole()->sent_at)->toBeNull();
    moduleSignIn($this, $learner->fresh());
    $this->get(route('dashboard'))->assertOk()->assertSee('data-role-update', false)->assertSee('celebration.webp');
    $this->get(route('account.edit'))->assertOk()->assertDontSee('data-role-update', false);
    $payload = $this->getJson(route('account.role-update'))->assertOk()->json('update');
    $this->postJson($payload['acknowledge'])->assertOk()->assertJsonPath('redirect', route('studio.index'));
    $this->postJson($payload['acknowledge'])->assertOk();
    $this->getJson(route('account.role-update'))->assertOk()->assertJsonPath('update', null)->assertJsonPath('watch', false);
    $this->get(route('notifications.index'))->assertSee('Instructor access approved');
});

test('Contributor approval notification is owned and old role notices cannot replay after another role change', function () {
    $learner = contributorQualifiedUser();
    $application = contributorSubmit($learner);
    $reviewer = moduleAccount(Role::Administrator);
    app(ContributorApplications::class)->review($reviewer, 'module-test-session', $application, contributorDecision());
    $payload = app(RoleChangeNotices::class)->snapshot($learner, 'module-test-session')['update'];
    expect($payload)->toMatchArray(['role' => 'Contributor', 'previous' => 'Learner', 'workspace' => route('challenges.index')]);
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->postJson($payload['acknowledge'])->assertNotFound();
    $learner->refresh()->forceFill(['account_role' => Role::Instructor])->save();
    expect(app(RoleChangeNotices::class)->snapshot($learner, 'module-test-session')['update'])->toBeNull();
});

test('rejected applications never celebrate promotion and approval notices roll back with failed audit', function () {
    $learner = moduleAccount(Role::Learner);
    $reviewer = moduleAccount(Role::Moderator);
    $application = pendingInstructor($learner);
    app(ReviewInstructorApplication::class)->handle($reviewer, 'module-test-session', $application, 1, 'Rejected', 'Please supply clearer proof.', true);
    expect(app(RoleChangeNotices::class)->snapshot($learner, 'module-test-session')['update'])->toBeNull();
    $application = pendingInstructor(moduleAccount(Role::Learner));
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('synthetic failure'));
    expect(fn () => app(ReviewInstructorApplication::class)->handle($reviewer, 'module-test-session', $application, 1, 'Approved', null, true))->toThrow(RuntimeException::class);
    expect(User::find($application->user_id)->account_role)->toBe(Role::Learner);
    expect(DB::table('notifications')->where('type', RoleChangeNotices::TYPE)->count())->toBe(0);
});
