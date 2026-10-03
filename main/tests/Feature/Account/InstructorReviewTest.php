<?php

use App\Actions\Account\ReviewInstructorApplication;
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendCreatorDecision;
use App\Mail\Account\CreatorDecision;
use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

function pendingInstructor(?User $user = null): InstructorApplication
{
    $user ??= User::factory()->create();
    Storage::fake('local');
    Storage::disk('local')->put('instructor-credentials/proof.pdf', '%PDF-test-private-proof');

    return InstructorApplication::create(['user_id' => $user->id, 'institution_name' => 'Test Institute', 'specialization' => 'Programming',
        'credential_disk' => 'local', 'credential_path' => 'instructor-credentials/proof.pdf', 'verification_status' => 'Pending']);
}

function signInReviewer(TestCase $test, Role $role = Role::Moderator): User
{
    $reviewer = User::factory()->create(['account_role' => $role]);
    $test->post(route('login.store'), ['email' => $reviewer->email, 'password' => 'password']);
    $test->withCredentials()->withCookie(config('session.cookie'), session()->getId());

    return $reviewer;
}

function instructorDecision(array $overrides = []): array
{
    return array_replace(['record_version' => 1, 'decision' => 'Approved', 'credibility_reviewed' => true], $overrides);
}

test('A10 authorized reviewers grant only Instructor access with atomic audit and pending mail', function (Role $role) {
    $application = pendingInstructor();
    $reviewer = signInReviewer($this, $role);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['account_role' => 'Admin', 'user_id' => $reviewer->id]))->assertOk();
    expect(User::find($application->user_id)->account_role)->toBe(Role::Instructor)
        ->and($application->fresh()->verification_status)->toBe('Approved')->and($application->fresh()->record_version)->toBe(2)
        ->and($application->fresh()->reviewed_by)->toBe($reviewer->id);
    $this->assertDatabaseCount('audit_events', 1);
    $this->assertDatabaseCount('creator_decision_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
    expect(DB::table('jobs')->value('payload'))->not->toContain('proof.pdf');
})->with([Role::Moderator, Role::Administrator]);

test('A10 rejection preserves account access and escapes reviewer feedback', function () {
    $application = pendingInstructor();
    signInReviewer($this);
    $notes = '<script>unsafe()</script>';
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['decision' => 'Rejected', 'verification_notes' => $notes]))->assertOk();
    $applicant = User::find($application->user_id);
    expect($applicant->account_role)->toBe(Role::Learner)->and($applicant->account_status)->toBe(AccountStatus::Active);
    $this->get(route('instructor-reviews.show', $application))->assertOk()->assertDontSee($notes, false)->assertSee('&lt;script&gt;', false);
});

test('A10 learner contributor and instructor roles cannot inspect or review private applications', function (Role $role) {
    $application = pendingInstructor();
    signInReviewer($this, $role);
    $this->get(route('instructor-reviews.index'))->assertForbidden();
    $this->get(route('instructor-reviews.show', $application))->assertForbidden();
    $this->get(route('instructor-reviews.credential', $application))->assertForbidden();
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertForbidden();
    $this->assertDatabaseCount('audit_events', 0);
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('A10 reviewers cannot approve their own application', function () {
    $reviewer = signInReviewer($this);
    $application = pendingInstructor($reviewer);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertForbidden();
    $this->get(route('instructor-reviews.credential', $application))->assertForbidden();
});

test('A10 credential download is private audited and served as an attachment', function () {
    $application = pendingInstructor();
    signInReviewer($this);
    $this->get(route('instructor-reviews.credential', $application))->assertOk()->assertDownload('instructor-credential.pdf')
        ->assertHeader('Content-Type', 'application/octet-stream')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
    $this->assertDatabaseHas('audit_events', ['event' => 'instructor_application.credential_downloaded']);
    expect(DB::table('audit_events')->value('context'))->not->toContain('proof.pdf');
    Storage::disk('local')->delete($application->credential_path);
    $this->get(route('instructor-reviews.credential', $application))->assertNotFound();
});

test('A10 stale and duplicate decisions cannot grant privileges or enqueue notifications twice', function () {
    $application = pendingInstructor();
    signInReviewer($this);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['record_version' => 2]))->assertJsonValidationErrors('decision');
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertOk();
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['decision' => 'Rejected', 'verification_notes' => 'Late rejection']))->assertJsonValidationErrors('decision');
    $this->assertDatabaseCount('audit_events', 1);
    $this->assertDatabaseCount('creator_decision_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('A10 approval rejects ineligible applicants and requires manual credibility confirmation', function (AccountStatus $status) {
    $applicant = User::factory()->create(['account_status' => $status, 'email_verified_at' => $status === AccountStatus::Unverified ? null : now()]);
    $application = pendingInstructor($applicant);
    signInReviewer($this);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertJsonValidationErrors('decision');
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['credibility_reviewed' => false]))->assertJsonValidationErrors('credibility_reviewed');
    expect($application->fresh()->verification_status)->toBe('Pending')->and($applicant->fresh()->account_role)->toBe(Role::Learner);
    $this->assertDatabaseCount('jobs', 0);
})->with([AccountStatus::Unverified, AccountStatus::Suspended, AccountStatus::Archived, AccountStatus::Deleted]);

test('A10 rejection requires feedback and all reviewer fields are bounded', function () {
    $application = pendingInstructor();
    signInReviewer($this);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['decision' => 'Rejected']))->assertJsonValidationErrors('verification_notes');
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['verification_notes' => str_repeat('x', 256)]))->assertJsonValidationErrors('verification_notes');
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision(['decision' => 'Admin']))->assertJsonValidationErrors('decision');
});

test('A10 reviewer authorization is rechecked under locks', function () {
    $application = pendingInstructor();
    $reviewer = signInReviewer($this);
    $reviewer->forceFill(['account_role' => Role::Learner])->save();
    expect(fn () => app(ReviewInstructorApplication::class)->handle($reviewer, session()->getId(), $application, 1, 'Approved', null, true))
        ->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('audit_events', 0);
});

test('A10 queue failure rolls back review role change and audit state', function () {
    $application = pendingInstructor();
    $reviewer = signInReviewer($this);
    Queue::shouldReceive('connection')->with('database')->andReturnSelf();
    Queue::shouldReceive('push')->andThrow(new RuntimeException('Test queue failure.'));
    expect(fn () => app(ReviewInstructorApplication::class)->handle($reviewer, session()->getId(), $application, 1, 'Approved', null, true))->toThrow(RuntimeException::class);
    expect($application->fresh()->verification_status)->toBe('Pending')->and(User::find($application->user_id)->account_role)->toBe(Role::Learner);
    $this->assertDatabaseCount('audit_events', 0);
    $this->assertDatabaseCount('creator_decision_deliveries', 0);
});

test('A10 notification retries are bounded sanitized and do not repeat successful delivery', function () {
    Mail::fake();
    $application = pendingInstructor();
    signInReviewer($this);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertOk();
    $job = new SendCreatorDecision(DB::table('creator_decision_deliveries')->value('id'));
    config(['account.notifications.mailer' => 'log']);
    expect(fn () => $job->handle())->toThrow(RuntimeException::class, 'Creator decision email could not be delivered.');
    expect(DB::table('creator_decision_deliveries')->value('failed_at'))->not->toBeNull();
    expect($application->fresh()->verification_status)->toBe('Approved');
    config(['account.notifications.mailer' => 'array']);
    $job->handle();
    $job->handle();
    Mail::assertSent(CreatorDecision::class, 1);
    expect(DB::table('creator_decision_deliveries')->value('sent_at'))->not->toBeNull();
});

test('A10 application status is visible only on its owners profile', function () {
    $application = pendingInstructor();
    $applicant = User::find($application->user_id);
    $this->post(route('login.store'), ['email' => $applicant->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('account.show'))->assertOk()->assertSee('Instructor application: Pending')->assertDontSee('proof.pdf');
});

test('A10 notifications cancel when the recipient email changes', function () {
    Mail::fake();
    $application = pendingInstructor();
    signInReviewer($this);
    $this->postJson(route('instructor-reviews.review', $application), instructorDecision())->assertOk();
    User::find($application->user_id)->update(['email' => 'changed@example.test']);
    (new SendCreatorDecision(DB::table('creator_decision_deliveries')->value('id')))->handle();
    Mail::assertNothingSent();
    expect(DB::table('creator_decision_deliveries')->value('cancelled_at'))->not->toBeNull();
});

test('A10 credential paths cannot escape private storage or use a public disk', function () {
    $application = pendingInstructor();
    signInReviewer($this);
    $application->update(['credential_path' => 'instructor-credentials/../other.pdf']);
    $this->get(route('instructor-reviews.credential', $application))->assertNotFound();
    $application->update(['credential_disk' => 'public', 'credential_path' => 'instructor-credentials/proof.pdf']);
    $this->get(route('instructor-reviews.credential', $application))->assertNotFound();
});

test('A10 privileged routes require authentication CSRF and independent throttles', function () {
    $application = pendingInstructor();
    $this->get(route('instructor-reviews.index'))->assertRedirect(route('login'));
    signInReviewer($this);
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('instructor-reviews.review', $application), [])->assertUnprocessable();
    }
    $this->postJson(route('instructor-reviews.review', $application), [])->assertTooManyRequests();
    $this->get(route('instructor-reviews.credential', $application))->assertOk();
    $this->app['env'] = 'production';
    $this->post(route('instructor-reviews.review', $application), instructorDecision())->assertStatus(419);
});
