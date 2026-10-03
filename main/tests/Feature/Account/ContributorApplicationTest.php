<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\EraseAccountFile;
use App\Jobs\Account\SendContributorNotice;
use App\Models\AccountFileErasure;
use App\Models\ContributorApplication;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\ContributorApplications;
use App\Services\Account\ContributorEligibility;
use App\Services\Account\InstructorApplications;
use App\Services\Account\SecureAccountMailer;
use App\Services\Administration\AuditRecorder;
use App\Services\Content\CourseLearning;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function contributorData(array $overrides = []): array
{
    return array_replace(['previous_application_id' => 0, 'request_message' => 'I would like to build programming quests.',
        'portfolio_link' => 'https://portfolio.example.test', 'credential_document' => creatorApplicationData()['credential_document'], 'confirmed' => true], $overrides);
}

function contributorDecision(array $overrides = []): array
{
    return array_replace(['record_version' => 1, 'decision' => 'Approved', 'moderator_feedback' => null, 'confirmed' => true], $overrides);
}

function contributorQualifiedUser(int $modules = 25, int $challenges = 50): User
{
    $user = moduleAccount(Role::Learner);
    $user->forceFill(['created_at' => now()->subDays(30)])->save();
    // Persist authoritative-record fixtures, with a real provider-fake pass as
    // the template. No test browser can write these records through an endpoint.
    for ($index = 1; $index <= $modules; $index++) {
        DB::table('learning_activity_days')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id,
            'level' => 'module-'.$index, 'kind' => 'game', 'template_version' => 1, 'business_date' => now()->toDateString(),
            'validated_input' => '{}', 'completed_at' => now()]);
    }
    if ($challenges > 0) {
        readyJudge();
        $challenge = challengeFixture(true);
        $attempt = submitAttempt($challenge, $user);
        evaluateAttempt($attempt);
        $submission = DB::table('challenge_submissions')->where('id', $attempt->id)->first();
        unset($submission->context_key);
        $revision = DB::table('coding_challenge_revisions')->where('id', $challenge->published_revision_id)->first();
        $challengeRow = DB::table('coding_challenges')->where('id', $challenge->id)->first();
        for ($index = 1; $index < $challenges; $index++) {
            $content = (array) $challengeRow;
            unset($content['id']);
            $content['published_revision_id'] = null;
            $content['status'] = 'Draft';
            $challengeId = DB::table('coding_challenges')->insertGetId($content);
            $copy = (array) $revision;
            unset($copy['id']);
            $copy['challenge_id'] = $challengeId;
            $revisionId = DB::table('coding_challenge_revisions')->insertGetId($copy);
            DB::table('coding_challenges')->where('id', $challengeId)->update(['published_revision_id' => $revisionId, 'status' => 'Published']);
            $participationId = DB::table('challenge_participations')->insertGetId(['user_id' => $user->id,
                'challenge_id' => $challengeId, 'attempts' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $copy = (array) $submission;
            $copy['id'] = (string) Str::uuid();
            $copy['participation_id'] = $participationId;
            $copy['challenge_id'] = $challengeId;
            $copy['revision_id'] = $revisionId;
            DB::table('challenge_submissions')->insert($copy);
        }
    }

    return $user;
}

function contributorSubmit(User $user, array $overrides = []): ContributorApplication
{
    Storage::fake('local');
    $data = contributorData($overrides);
    app(ContributorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']);

    return ContributorApplication::where('user_id', $user->id)->latest('id')->firstOrFail();
}

test('A09 eligibility requires all three exact thresholds and counts distinct validated identities', function () {
    $user = contributorQualifiedUser();
    $service = app(ContributorEligibility::class);
    expect($service->snapshot($user))->toBe(['account_age_days' => 30, 'completed_modules_count' => 25, 'completed_challenges_count' => 50, 'eligible' => true]);
    $row = (array) DB::table('learning_activity_days')->where('user_id', $user->id)->first();
    $row['id'] = (string) Str::uuid();
    $row['business_date'] = now()->subDay()->toDateString();
    DB::table('learning_activity_days')->insert($row);
    $row['id'] = (string) Str::uuid();
    $row['level'] = 'sequences';
    DB::table('learning_activity_days')->insert($row);
    $submission = (array) DB::table('challenge_submissions')->first();
    unset($submission['context_key']);
    $submission['id'] = (string) Str::uuid();
    $submission['attempt'] = 2;
    $submission['confirmation_id'] = (string) Str::uuid();
    DB::table('challenge_submissions')->insert($submission);
    expect($service->snapshot($user)['completed_modules_count'])->toBe(25)->and($service->snapshot($user)['completed_challenges_count'])->toBe(50);
    $user->forceFill(['created_at' => now()->subDays(30)->addSecond()])->save();
    expect($service->snapshot($user)['eligible'])->toBeFalse();
    $user->forceFill(['created_at' => now()->subDays(30)])->save();
    DB::table('learning_activity_days')->where('user_id', $user->id)->where('level', 'module-25')->delete();
    expect($service->snapshot($user)['eligible'])->toBeFalse();
    DB::table('challenge_submissions')->where('challenge_id', DB::table('coding_challenges')->max('id'))->update(['status' => 'Failed', 'passed_cases' => 0]);
    expect($service->snapshot($user)['completed_challenges_count'])->toBe(49);
});

test('A09 module totals combine standalone and course clearances without counting reused lessons twice', function () {
    $user = moduleAccount(Role::Learner);
    $module = moduleFixture(true);
    $first = courseFixture(true, $module);
    $second = courseFixture(true, $module, ['title' => 'Another garden journey']);
    $service = app(CourseLearning::class);
    foreach ([$first, $second] as $course) {
        joinCourse($course, $user);
        $slot = $course->publishedRevision->modules()->sole();
        $service->lesson($user, 'module-test-session', $course->id, $slot->id);
        expect(app(ContributorEligibility::class)->snapshot($user)['completed_modules_count'])->toBe($course->id === $first->id ? 0 : 1);
        $service->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin());
    }
    app(LearningProgression::class)->recordModule($user, 'module-test-session', $module->id, $module->published_revision_id, 'game', courseWin());
    expect(app(ContributorEligibility::class)->snapshot($user)['completed_modules_count'])->toBe(1);
});

test('A09 eligible Learners submit private applications and notify both reviewing roles without elevation', function () {
    Storage::fake('local');
    $user = contributorQualifiedUser();
    $moderator = moduleAccount(Role::Moderator);
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $user);
    $this->get(route('contributor-application.create'))->assertOk()->assertSee('Become a Contributor');
    $this->post(route('contributor-application.store'), contributorData(['request_message' => str_repeat('é', 500)]))->assertRedirect(route('contributor-application.create'));
    $application = ContributorApplication::sole();
    expect($user->fresh()->account_role)->toBe(Role::Learner)->and($application->completed_modules_count)->toBe(25);
    Storage::disk('local')->assertExists($application->credential_path);
    expect($application->toArray())->not->toHaveKey('credential_path');
    $this->get(route('contributor-application.create'))->assertDontSee($application->credential_path)->assertDontSee('name="credential_document"', false);
    $deliveries = DB::table('contributor_notice_deliveries')->get();
    expect($deliveries)->toHaveCount(3);
    foreach ($deliveries as $delivery) {
        (new SendContributorNotice($delivery->id))->handle();
        (new SendContributorNotice($delivery->id))->handle();
    }
    expect($moderator->notifications()->count())->toBe(1)->and($admin->notifications()->count())->toBe(1);
    $this->post(route('contributor-application.store'), contributorData())->assertSessionHasErrors('application');
    $this->assertDatabaseCount('contributor_applications', 1);
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
});

test('A09 rejects missing thresholds protected counters unsafe URLs invalid files and long text', function (array $override, string $field) {
    Storage::fake('local');
    moduleSignIn($this, User::factory()->create());
    $this->post(route('contributor-application.store'), contributorData($override))->assertSessionHasErrors($field);
    $this->assertDatabaseCount('contributor_applications', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    [[], 'application'], [['completed_modules_count' => 25], 'completed_modules_count'], [['account_role' => 'Contributor'], 'account_role'],
    [['request_message' => str_repeat('x', 501)], 'request_message'], [['portfolio_link' => 'javascript:alert(1)'], 'portfolio_link'],
    [['credential_document' => UploadedFile::fake()->createWithContent('proof.pdf', '<script>bad</script>')->mimeType('text/html')], 'credential_document'],
    [['confirmed' => false], 'confirmed'],
]);

test('A09 only verified Active Learners can submit and peer accounts cannot read credentials', function (Role $role) {
    $user = moduleAccount($role);
    moduleSignIn($this, $user);
    $this->post(route('contributor-application.store'), contributorData())->assertForbidden();
})->with([Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator]);

test('A09 restricted accounts and stale sessions cannot admit applications', function (AccountStatus $status) {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $user->forceFill(['account_status' => $status])->save();
    $data = contributorData();
    expect(fn () => app(ContributorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('contributor_applications', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([AccountStatus::Archived, AccountStatus::Suspended, AccountStatus::Deleted, AccountStatus::Unverified]);

test('G05 reviewers decide once and approval grants only Contributor while rejection preserves history', function (Role $role) {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $reviewer = moduleAccount($role);
    moduleSignIn($this, $reviewer);
    $this->get(route('contributor-reviews.index'))->assertOk();
    $this->get(route('contributor-reviews.show', $application))->assertOk()->assertDontSee($application->credential_path);
    $this->get(route('contributor-reviews.credential', $application))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->post(route('contributor-reviews.review', $application), contributorDecision(['moderator_feedback' => str_repeat('é', 500)]))->assertRedirect(route('contributor-reviews.show', $application));
    expect($user->fresh()->account_role)->toBe(Role::Contributor)->and($application->fresh()->approval_status)->toBe('Approved');
    $this->post(route('contributor-reviews.review', $application), contributorDecision())->assertSessionHasErrors('decision');
    expect(DB::table('audit_events')->where('event', 'contributor_application.reviewed')->count())->toBe(1);
    $delivery = DB::table('contributor_notice_deliveries')->where('kind', 'Approved')->sole();
    (new SendContributorNotice($delivery->id))->handle();
    expect($user->notifications()->count())->toBe(1);
})->with([Role::Moderator, Role::Administrator]);

test('A09 rejected resubmissions preserve each decision and block overlapping Instructor applications', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $path = $application->credential_path;
    $instructorData = creatorApplicationData();
    expect(fn () => app(InstructorApplications::class)->submit($user, 'module-test-session', $instructorData, $instructorData['credential_document']))->toThrow(ValidationException::class);
    $reviewer = moduleAccount(Role::Moderator);
    app(ContributorApplications::class)->review($reviewer, 'module-test-session', $application, contributorDecision(['decision' => 'Rejected', 'moderator_feedback' => 'Add more work.']));
    $data = contributorData(['previous_application_id' => $application->id]);
    app(ContributorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']);
    expect($user->fresh()->account_role)->toBe(Role::Learner)->and($application->fresh()->moderator_feedback)->toBe('Add more work.');
    Storage::disk('local')->assertExists($path);
    $this->assertDatabaseCount('contributor_applications', 2);
    moduleSignIn($this, $user);
    $this->get(route('instructor-application.create'))->assertSee('Contributor application is awaiting review')->assertDontSee('name="credential_document"', false);
});

test('A09 pending Instructor applications block Contributor requests without losing the existing credential', function () {
    $user = contributorQualifiedUser();
    $existing = pendingInstructor($user);
    $data = contributorData();
    expect(fn () => app(ContributorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']))->toThrow(ValidationException::class);
    Storage::disk('local')->assertExists($existing->credential_path);
    $this->assertDatabaseCount('contributor_applications', 0);
});

test('G05 fresh applicant status reviewer session and cross-user authorization are enforced', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $peer = moduleAccount(Role::Learner);
    moduleSignIn($this, $peer);
    $this->get(route('contributor-reviews.credential', $application))->assertForbidden();
    $this->post(route('contributor-reviews.review', $application), contributorDecision())->assertForbidden();
    $reviewer = moduleAccount(Role::Moderator);
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    expect(fn () => app(ContributorApplications::class)->review($reviewer, 'module-test-session', $application, contributorDecision()))->toThrow(ValidationException::class);
    $reviewer->forceFill(['account_status' => AccountStatus::Suspended])->save();
    expect(fn () => app(ContributorApplications::class)->review($reviewer, 'module-test-session', $application, contributorDecision(['decision' => 'Rejected'])))->toThrow(AuthorizationException::class);
    expect($application->fresh()->approval_status)->toBe('Pending');
});

test('A09 audit failure rolls back admission and removes its uncommitted private file', function () {
    Storage::fake('local');
    $user = contributorQualifiedUser();
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    $data = contributorData();
    expect(fn () => app(ContributorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('contributor_applications', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('G05 stale decisions invalid confirmations and oversized feedback cannot change roles', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $reviewer = moduleAccount(Role::Moderator);
    moduleSignIn($this, $reviewer);
    foreach ([['record_version' => 99], ['confirmed' => false], ['moderator_feedback' => str_repeat('x', 501)], ['decision' => 'Administrator']] as $override) {
        $this->post(route('contributor-reviews.review', $application), contributorDecision($override))->assertSessionHasErrors();
    }
    expect($application->fresh()->approval_status)->toBe('Pending')->and($user->fresh()->account_role)->toBe(Role::Learner);
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    expect(fn () => app(ContributorApplications::class)->review($reviewer->fresh(), session()->getId(), $application, contributorDecision()))->toThrow(RuntimeException::class);
    expect($user->fresh()->account_role)->toBe(Role::Learner)->and($application->fresh()->approval_status)->toBe('Pending');
});

test('A09 application routes require authentication and CSRF', function () {
    $this->get(route('contributor-application.create'))->assertRedirect(route('login'));
    $this->post(route('contributor-application.store'), contributorData())->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create());
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('contributor-application.store'), contributorData())->assertStatus(419);
});

test('G05 notification failure keeps approval durable and retries without duplicate in-app notices', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $reviewer = moduleAccount(Role::Moderator);
    app(ContributorApplications::class)->review($reviewer, 'module-test-session', $application, contributorDecision());
    $delivery = DB::table('contributor_notice_deliveries')->where('kind', 'Approved')->sole();
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->andThrow(new RuntimeException('secret transport diagnostics'));
    expect(fn () => (new SendContributorNotice($delivery->id))->handle())->toThrow(RuntimeException::class, 'Contributor application notice could not be delivered.');
    expect($user->fresh()->account_role)->toBe(Role::Contributor)->and($user->notifications()->count())->toBe(1);
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once();
    (new SendContributorNotice($delivery->id))->handle();
    (new SendContributorNotice($delivery->id))->handle();
    expect($user->notifications()->count())->toBe(1);
});

test('Delete Account removes Contributor history staff notices and queues all private supporting files', function () {
    $user = contributorQualifiedUser();
    $reviewer = moduleAccount(Role::Moderator);
    $application = contributorSubmit($user);
    foreach (DB::table('contributor_notice_deliveries')->get() as $delivery) {
        (new SendContributorNotice($delivery->id))->handle();
    }
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount('contributor_applications', 0);
    $this->assertDatabaseCount('contributor_notice_deliveries', 0);
    expect($reviewer->notifications()->count())->toBe(0);
    (new EraseAccountFile(AccountFileErasure::sole()->id))->handle();
    Storage::disk('local')->assertMissing($application->credential_path);
});

test('A09 PostgreSQL prevents duplicate Pending applications invalid thresholds and history rollback', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $copy = $application->getAttributes();
    unset($copy['id']);
    expect(fn () => DB::transaction(fn () => DB::table('contributor_applications')->insert($copy)))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('contributor_applications')->where('id', $application->id)->update(['completed_modules_count' => 24])))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_10_03_000017_create_contributor_applications.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});
