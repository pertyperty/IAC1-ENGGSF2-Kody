<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AccountRecovery;
use App\Models\EmailVerification;
use App\Models\InstructorApplication;
use App\Models\TowerLevel;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\EmailVerificationService;
use App\Services\Administration\ModeratorAppointments;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use App\Services\Engagement\ContentFeedback;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

test('tower overlapping validated clears save one checkpoint clearance and streak without XP', function () {
    $this->seed(TowerLevelSeeder::class);
    $level = TowerLevel::where('position', 1)->sole();
    $user = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('tower-complete', ['actor_id' => $user->id, 'session_id' => 'module-test-session',
        'level_id' => $level->id, 'revision_id' => $level->current_revision_id], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    expect($results)->toBe(['saved', 'saved']);
    $this->assertDatabaseCount('tower_clearances', 1);
    $this->assertDatabaseCount('tower_stage_completions', 1);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('xp_awards', 0);
});

test('A03 Google simultaneous callback claims permit only one provider exchange', function () {
    $sessionId = Str::random(40);
    $attemptId = (string) Str::uuid();
    DB::table('google_auth_attempts')->insert(['id' => $attemptId, 'session_hash' => hash('sha256', $sessionId),
        'state_hash' => hash('sha256', 'test-state'), 'intent' => 'login', 'created_at' => now(), 'expires_at' => now()->addMinutes(5)]);
    $results = simultaneousAccountRequests('google-claim', ['session_id' => $sessionId, 'attempt_id' => $attemptId, 'state' => 'test-state'],
        fn () => DB::table('google_auth_attempts')->where('id', $attemptId)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['claimed', 'invalid'])->and(DB::table('google_auth_attempts')->value('consumed_at'))->not->toBeNull();
});

test('A03 Google concurrent sign-ins authenticate one session and prompt the other', function () {
    $user = User::factory()->create();
    $hash = googleIdentity($user);
    $results = simultaneousAccountRequests('google-login', ['subject_hash' => $hash], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['authenticated', 'conflict']);
});

test('A06 Google concurrent links preserve unique ownership and one audit', function () {
    $sessionId = Str::random(40);
    $users = User::factory()->count(2)->create(['active_session_hash' => hash('sha256', $sessionId), 'active_session_expires_at' => now()->addHour()]);
    $results = simultaneousAccountRequests('google-link', ['session_id' => $sessionId, 'user_ids' => $users->pluck('id')->all(), 'subject_hash' => hash('sha256', 'shared-google-subject')],
        fn () => DB::statement('LOCK TABLE google_identities IN SHARE MODE'));
    sort($results);
    expect($results)->toBe(['linked', 'rejected'])->and(DB::table('google_identities')->count())->toBe(1)
        ->and(DB::table('audit_events')->where('event', 'account.google-linked')->count())->toBe(1);
});

test('D04 D09 C07 simultaneous deletion commits one purge and audit', function (string $kind) {
    $content = moderationFixture($kind);
    $results = simultaneousAccountRequests('content-delete', ['actor_ids' => [$content->created_by, $content->created_by],
        'actions' => ['delete', 'delete'], 'kind' => $kind, 'content_id' => $content->id, 'version' => $content->record_version],
        fn () => $content->newQuery()->whereKey($content->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['deleted', 'missing'])->and($content->fresh())->toBeNull();
    expect(DB::table('audit_events')->where('event', $kind.'.deleted')->count())->toBe(1);
})->with(['module', 'course', 'challenge']);

test('D04 a simultaneous opening and deletion preserve either the learner reference or complete deletion', function () {
    $content = moduleFixture(true);
    $learner = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('content-delete', ['actor_ids' => [$content->created_by, $learner->id],
        'actions' => ['delete', 'open'], 'kind' => 'module', 'content_id' => $content->id, 'version' => $content->record_version],
        fn () => $content->newQuery()->whereKey($content->id)->lockForUpdate()->first());
    sort($results);
    if ($content->fresh() === null) {
        expect($results)->toBe(['deleted', 'missing'])->and(DB::table('content_accesses')->where('module_id', $content->id)->count())->toBe(0);
    } else {
        expect($results)->toBe(['duplicate', 'opened'])->and(DB::table('content_accesses')->where('module_id', $content->id)->count())->toBe(1);
    }
});

test('B10 simultaneous reactions serialize duplicate retries competing choices and independent users', function (string $case) {
    $item = moduleFixture(true);
    $first = moduleAccount(Role::Learner);
    $second = $case === 'independent' ? moduleAccount(Role::Learner) : $first;
    $service = app(ContentFeedback::class);
    foreach ([$first, $second] as $user) {
        $service->read($user, 'module-test-session', 'module', $item->id, true);
    }
    $results = simultaneousAccountRequests('content-react', ['actor_ids' => [$first->id, $second->id], 'module_id' => $item->id,
        'version' => 0, 'reactions' => ['Like', $case === 'competing' ? 'Helpful' : 'Like']],
        fn () => $item->newQuery()->whereKey($item->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe($case === 'competing' ? ['duplicate', 'saved'] : ['saved', 'saved']);
    expect(DB::table('content_reactions')->count())->toBe($case === 'independent' ? 2 : 1);
    expect(DB::table('content_reactions')->where('record_version', '!=', 1)->count())->toBe(0);
    $state = $service->read($first, 'module-test-session', 'module', $item->id);
    expect(array_sum($state['counts']))->toBe($case === 'independent' ? 2 : 1);
})->with(['duplicate', 'competing', 'independent']);

test('G12 G13 simultaneous FAQ confirmations commit one content version and audit', function (string $action) {
    $entry = faqFixture();
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $results = simultaneousAccountRequests('faq-change', ['actor_ids' => [$first->id, $second->id], 'entry_id' => $entry->id,
        'action' => $action, 'data' => faqData(['answer' => 'Changed answer'])], fn () => $entry->newQuery()->whereKey($entry->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['changed', 'duplicate'])->and($entry->fresh()->record_version)->toBe(2);
    expect(DB::table('audit_events')->where('event', $action === 'update' ? 'faq.updated' : 'faq.deleted')->count())->toBe(1);
})->with(['update', 'delete']);

test('G11 simultaneous duplicate FAQ creations commit one entry and audit', function () {
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $results = simultaneousAccountRequests('faq-create', ['actor_ids' => [$first->id, $second->id], 'data' => faqData()],
        fn () => DB::statement('LOCK TABLE faq_entries IN SHARE MODE'));
    sort($results);
    expect($results)->toBe(['created', 'duplicate']);
    expect(DB::table('faq_entries')->count())->toBe(1)->and(DB::table('audit_events')->where('event', 'faq.created')->count())->toBe(1);
});

test('G09 G10 competing administrator confirmations commit one preset transition and audit', function (string $action) {
    $preset = presetFixture();
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $results = simultaneousAccountRequests('preset-change', ['actor_ids' => [$first->id, $second->id], 'preset_id' => $preset->id,
        'action' => $action, 'data' => presetData(['title' => 'Changed'])], fn () => $preset->newQuery()->whereKey($preset->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['changed', 'duplicate'])->and($preset->fresh()->record_version)->toBe(2);
    expect(DB::table('game_preset_revisions')->count())->toBe($action === 'update' ? 2 : 1);
    expect(DB::table('audit_events')->where('event', $action === 'update' ? 'game_preset.updated' : 'game_preset.inactivated')->count())->toBe(1);
})->with(['update', 'inactivate']);

test('G08 competing same-name creations commit one preset revision and audit', function () {
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $results = simultaneousAccountRequests('preset-create', ['actor_ids' => [$first->id, $second->id], 'data' => presetData()],
        fn () => DB::statement('LOCK TABLE game_presets IN SHARE MODE'));
    sort($results);
    expect($results)->toBe(['created', 'duplicate']);
    expect(DB::table('game_presets')->count())->toBe(1)->and(DB::table('game_preset_revisions')->count())->toBe(1);
    expect(DB::table('audit_events')->where('event', 'game_preset.created')->count())->toBe(1);
});

test('G06 simultaneous withdrawal and restoration confirmations commit one state version audit and notice', function (string $kind, string $action) {
    $item = moderationFixture($kind);
    $first = moduleAccount(Role::Moderator);
    $second = moduleAccount(Role::Administrator);
    if ($action === 'Restored') {
        staffWithdraw($kind, $item, $first);
    }
    $results = simultaneousAccountRequests('content-moderate', ['actor_ids' => [$first->id, $second->id], 'kind' => $kind, 'content_id' => $item->id,
        'data' => moderationData($item, ['action' => $action])], fn () => $item->newQuery()->whereKey($item->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'moderated']);
    expect(DB::table('content_moderation_actions')->where('action', $action)->count())->toBe(1)
        ->and($item->fresh()->isWithdrawn())->toBe($action === 'Withdrawn');
    expect(DB::table('audit_events')->where('event', 'content.'.strtolower($action))->count())->toBe(1);
})->with([['module', 'Withdrawn'], ['module', 'Restored'], ['course', 'Withdrawn'], ['course', 'Restored'], ['challenge', 'Withdrawn'], ['challenge', 'Restored']]);

test('G02 support simultaneous confirmations commit one correction version audit and notice', function () {
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('support-correct', ['actor_ids' => [$first->id, $second->id], 'target_id' => $target->id,
        'data' => supportCorrectionData($target)], fn () => User::whereKey($target->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['corrected', 'duplicate']);
    expect(DB::table('account_support_corrections')->count())->toBe(1)->and($target->fresh()->profile_version)->toBe(2);
    expect(DB::table('audit_events')->where('event', 'account.support-corrected')->count())->toBe(1)->and(DB::table('jobs')->count())->toBe(1);
});

test('G02 simultaneous appointments and removals preserve one prior role and commit one transition', function (string $action) {
    $first = moduleAccount(Role::Administrator);
    $second = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Instructor);
    if ($action === 'Removed') {
        app(ModeratorAppointments::class)->change($first, 'module-test-session', $target, moderatorChangeData($target));
    }
    $results = simultaneousAccountRequests('moderator-change', ['actor_ids' => [$first->id, $second->id], 'target_id' => $target->id,
        'data' => moderatorChangeData($target, ['action' => $action])], fn () => User::whereKey($target->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['changed', 'duplicate']);
    expect(DB::table('account_role_changes')->where('action', $action)->count())->toBe(1)
        ->and($target->fresh()->account_role)->toBe($action === 'Appointed' ? Role::Moderator : Role::Instructor)
        ->and($target->fresh()->moderator_prior_role)->toBe($action === 'Appointed' ? Role::Instructor : null);
    expect(DB::table('audit_events')->where('event', 'account.moderator-'.strtolower($action))->count())->toBe(1);
})->with(['Appointed', 'Removed']);

test('G03 G04 simultaneous staff confirmations commit one state transition and audit', function (string $action) {
    $first = moduleAccount(Role::Moderator);
    $second = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    if ($action === 'Reinstated') {
        $target->forceFill(['account_status' => AccountStatus::Suspended])->save();
    }
    $results = simultaneousAccountRequests('account-enforce', ['actor_ids' => [$first->id, $second->id], 'target_id' => $target->id,
        'data' => enforcementData($target, ['action' => $action])], fn () => User::whereKey($target->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'enforced']);
    expect(DB::table('account_enforcements')->count())->toBe(1)->and($target->fresh()->profile_version)->toBe(2);
    expect(DB::table('audit_events')->where('event', 'account.'.strtolower($action))->count())->toBe(1);
})->with(['Suspended', 'Reinstated']);

test('A09 concurrent same-kind and cross-kind role applications admit only one Pending request', function (bool $mixed) {
    Storage::fake('local');
    $user = contributorQualifiedUser();
    $results = simultaneousAccountRequests('role-apply', ['actor_id' => $user->id, 'mixed' => $mixed,
        'storage_root' => Storage::disk('local')->path(''), 'data' => ['previous_application_id' => 0, 'request_message' => 'Build quests.', 'confirmed' => true],
        'instructor_data' => ['record_version' => 0, 'institution_name' => 'Teaching Institute', 'specialization' => 'Programming']],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['applied', 'duplicate'])->and(Storage::disk('local')->allFiles())->toHaveCount(1);
    expect(DB::table('contributor_applications')->where('approval_status', 'Pending')->count()
        + DB::table('instructor_applications')->where('verification_status', 'Pending')->count())->toBe(1);
})->with([false, true]);

test('G05 concurrent reviews grant Contributor and record a single decision and outcome', function () {
    $user = contributorQualifiedUser();
    $application = contributorSubmit($user);
    $first = moduleAccount(Role::Moderator);
    $second = moduleAccount(Role::Administrator);
    $results = simultaneousAccountRequests('contributor-review', ['actor_ids' => [$first->id, $second->id],
        'application_id' => $application->id, 'data' => contributorDecision()],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed'])->and($user->fresh()->account_role)->toBe(Role::Contributor);
    expect(DB::table('audit_events')->where('event', 'contributor_application.reviewed')->count())->toBe(1);
    expect(DB::table('contributor_notice_deliveries')->where('kind', 'Approved')->count())->toBe(1);
});

test('Delete Account simultaneous confirmations remove data and write one deletion audit', function () {
    $user = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('account-delete', ['actor_id' => $user->id, 'session_id' => 'module-test-session', 'data' => deletionData($user)],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['deleted', 'revoked']);
    expect(DB::table('audit_events')->where('event', 'account.deleted')->count())->toBe(1);
});

afterEach(function () {
    // Disposable kody_test only; production down migrations preserve credential history.
    $this->artisan('migrate:fresh')->assertSuccessful();
});

test('A07 competing archive confirmations change status and audit exactly once', function () {
    $user = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('account-archive', ['actor_id' => $user->id, 'session_id' => 'module-test-session', 'data' => archiveData($user)],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['archived', 'revoked']);
    expect(DB::table('audit_events')->where('event', 'account.archived')->count())->toBe(1);
});

test('A06 simultaneous creator applications save one pending version and clean up the losing upload', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('creator-apply', ['actor_id' => $user->id, 'session_id' => 'module-test-session',
        'storage_root' => Storage::disk('local')->path(''), 'data' => ['record_version' => 0, 'institution_name' => 'Teaching Institute', 'specialization' => 'Programming']],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['applied', 'duplicate'])->and(Storage::disk('local')->allFiles())->toHaveCount(1);
    $this->assertDatabaseCount('instructor_applications', 1);
    $this->assertDatabaseCount('instructor_application_versions', 1);
    expect(DB::table('audit_events')->where('event', 'instructor_application.submitted')->count())->toBe(1);
});

test('A06 overlapping profile saves commit one version and one audit without stale overwrites', function () {
    $user = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('profile-edit', ['actor_id' => $user->id, 'session_id' => 'module-test-session', 'data' => profileEditData($user)],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'saved'])->and($user->fresh()->profile_version)->toBe(2);
    expect(DB::table('audit_events')->where('event', 'account.profile-updated')->count())->toBe(1);
});

test('C03 overlapping final attempts cannot exceed the lifetime budget or create two active evaluations', function (bool $distinct) {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    for ($i = 0; $i < 2; $i++) {
        evaluateAttempt(submitAttempt($challenge, $user));
    }
    $results = simultaneousAccountRequests('challenge-attempt', ['actor_id' => $user->id, 'session_id' => 'module-test-session',
        'challenge_id' => $challenge->id, 'judge_config' => judgeConfiguration(), 'distinct' => $distinct, 'data' => attemptData($challenge)],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe($distinct ? ['attempted', 'duplicate'] : ['attempted', 'attempted']);
    $this->assertDatabaseCount('challenge_submissions', 3);
    $this->assertDatabaseCount('jobs', 3);
    expect(DB::table('challenge_participations')->value('attempts'))->toBe(3);
    expect(DB::table('challenge_submissions')->whereIn('status', ['Queued', 'Evaluating'])->count())->toBe(1);
})->with([true, false]);

test('C05 overlapping challenge edits preserve one new revision and its test snapshot', function () {
    $challenge = challengeFixture();
    $results = simultaneousAccountRequests('challenge-save', ['actor_id' => $challenge->created_by, 'session_id' => 'module-test-session', 'challenge_id' => $challenge->id, 'data' => challengeData()],
        fn () => User::whereKey($challenge->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'saved']);
    $this->assertDatabaseCount('coding_challenge_revisions', 2);
    $this->assertDatabaseCount('challenge_test_cases', 4);
});

test('C02 overlapping challenge reviews publish audit and notify exactly once', function () {
    $challenge = challengeFixture();
    app(ChallengePublishing::class)->submit(User::find($challenge->created_by), 'module-test-session', $challenge, 1);
    $reviewer = moduleAccount(Role::Moderator);
    $results = simultaneousAccountRequests('challenge-review', ['actor_id' => $reviewer->id, 'session_id' => 'module-test-session', 'challenge_id' => $challenge->id],
        fn () => User::whereKey($challenge->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed']);
    expect(DB::table('audit_events')->where('event', 'challenge.reviewed')->count())->toBe(1);
    expect(DB::table('notifications')->where('type', 'challenge.reviewed')->count())->toBe(1);
});

test('B03 overlapping free enrollments create one pinned grant and one audit', function () {
    $course = courseFixture(true);
    $learner = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('course-enroll', ['actor_id' => $learner->id, 'session_id' => 'module-test-session', 'course_id' => $course->id, 'revision_id' => $course->published_revision_id],
        fn () => User::whereKey($learner->id)->lockForUpdate()->first());
    expect($results)->toBe(['enrolled', 'enrolled']);
    $this->assertDatabaseCount('course_enrollments', 1);
    expect(DB::table('audit_events')->where('event', 'course.enrolled')->count())->toBe(1);
});

test('B04 overlapping course wins save one clearance activity and streak', function () {
    $course = courseFixture(true);
    $learner = moduleAccount(Role::Learner);
    joinCourse($course, $learner);
    $slot = $course->publishedRevision->modules->sole();
    $results = simultaneousAccountRequests('course-complete', ['actor_id' => $learner->id, 'session_id' => 'module-test-session', 'course_id' => $course->id, 'slot_id' => $slot->id],
        fn () => User::whereKey($learner->id)->lockForUpdate()->first());
    expect($results)->toBe(['saved', 'saved']);
    $this->assertDatabaseCount('course_module_progress', 1);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $learner->id, 'current_streak' => 1]);
});

test('D06 concurrent course edits save one replacement and reject stale composition', function () {
    $course = courseFixture();
    $ids = $course->latestRevision->modules->pluck('module_id')->all();
    $results = simultaneousAccountRequests('course-save', ['actor_id' => $course->created_by, 'session_id' => 'module-test-session', 'course_id' => $course->id, 'data' => courseData($ids)],
        fn () => User::whereKey($course->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'saved'])->and($course->fresh()->record_version)->toBe(2);
    $this->assertDatabaseCount('course_revisions', 2);
    $this->assertDatabaseCount('course_revision_modules', 2);
});

test('G06 concurrent course reviews publish and notify only once', function () {
    $course = courseFixture();
    app(CoursePublishing::class)->submit(User::find($course->created_by), 'module-test-session', $course, 1);
    $reviewer = moduleAccount(Role::Moderator);
    $results = simultaneousAccountRequests('course-review', ['actor_id' => $reviewer->id, 'session_id' => 'module-test-session', 'course_id' => $course->id],
        fn () => User::whereKey($course->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed'])->and($course->fresh()->status)->toBe('Published');
    expect(DB::table('audit_events')->where('event', 'course.reviewed')->count())->toBe(1);
    expect(DB::table('notifications')->where('type', 'course.reviewed')->count())->toBe(1);
});

test('D02 concurrent draft edits save one new revision and reject the stale edit', function () {
    $module = moduleFixture();
    $results = simultaneousAccountRequests('module-save', ['actor_id' => $module->created_by, 'session_id' => 'module-test-session', 'module_id' => $module->id, 'data' => moduleData()],
        fn () => User::whereKey($module->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'saved'])->and($module->fresh()->record_version)->toBe(2);
    $this->assertDatabaseCount('module_revisions', 2);
    $this->assertDatabaseCount('audit_events', 2);
});

test('G06 concurrent publication reviews publish and audit only once', function () {
    $module = moduleFixture();
    app(ModulePublishing::class)->submit(User::find($module->created_by), 'module-test-session', $module, 1);
    $reviewer = moduleAccount(Role::Moderator);
    $results = simultaneousAccountRequests('module-review', ['actor_id' => $reviewer->id, 'session_id' => 'module-test-session', 'module_id' => $module->id],
        fn () => User::whereKey($module->created_by)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed'])->and($module->fresh()->status)->toBe('Published');
    $this->assertDatabaseCount('audit_events', 3);
    $this->assertDatabaseCount('notifications', 1);
});

test('creator adventure overlapping wins save one activity and one streak day', function () {
    $module = moduleFixture(true);
    $learner = moduleAccount(Role::Learner);
    $results = simultaneousAccountRequests('module-complete', ['actor_id' => $learner->id, 'session_id' => 'module-test-session', 'module_id' => $module->id, 'revision_id' => $module->published_revision_id],
        fn () => User::whereKey($learner->id)->lockForUpdate()->first());
    expect($results)->toBe(['saved', 'saved']);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $learner->id, 'current_streak' => 1]);
});

test('A10 concurrent reviews apply one role elevation audit and notification', function () {
    $applicant = User::factory()->create();
    $application = InstructorApplication::create(['user_id' => $applicant->id, 'institution_name' => 'Test Institute', 'specialization' => 'Coding', 'credential_disk' => 'local', 'credential_path' => 'instructor-credentials/test.pdf']);
    $reviewer = User::factory()->create(['account_role' => Role::Moderator, 'active_session_hash' => hash('sha256', 'test-review-session'), 'active_session_expires_at' => now()->addHour()]);
    $results = simultaneousAccountRequests('creator-review', ['actor_id' => $reviewer->id, 'session_id' => 'test-review-session', 'application_id' => $application->id], fn () => User::whereKey($applicant->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed'])->and($applicant->fresh()->account_role)->toBe(Role::Instructor);
    $this->assertDatabaseCount('audit_events', 1);
    $this->assertDatabaseCount('creator_decision_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('game-first progression overlapping wins cannot duplicate daily streak or level grants', function () {
    $user = User::factory()->create(['active_session_hash' => hash('sha256', 'test-learning-session'), 'active_session_expires_at' => now()->addHour()]);
    $results = simultaneousAccountRequests('learning-complete', ['user_id' => $user->id, 'session_id' => 'test-learning-session'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    expect($results)->toBe(['saved', 'saved']);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_level_completions', 1);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'current_streak' => 1, 'longest_streak' => 1]);
});

test('A04 simultaneous recovery requests cannot exceed the hourly cap', function () {
    $user = User::factory()->create();
    app(AccountRecoveryService::class)->request($user);
    AccountRecovery::query()->update(['request_count' => 4, 'last_requested_at' => now()->subMinutes(2)]);
    $results = simultaneousAccountRequests('recover-request', ['user_id' => $user->id], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['limited', 'queued'])->and(AccountRecovery::sole()->request_count)->toBe(5);
    $this->assertDatabaseCount('jobs', 2);
});

test('A04 simultaneous password resets consume one recovery token exactly once', function () {
    $user = User::factory()->create();
    $service = app(AccountRecoveryService::class);
    $service->request($user);
    $proof = $service->authorization(VerificationDelivery::sole()->token);
    $results = simultaneousAccountRequests('recover-complete', ['proof' => $proof, 'password' => 'NewStrongPass12!'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['invalid', 'recovered'])->and(AccountRecovery::sole()->token_hash)->toBeNull();
});

function simultaneousAccountRequests(string $mode, array $input, Closure $lock): array
{
    $processes = [];
    $inputPath = tempnam(sys_get_temp_dir(), 'kody-concurrency-');
    file_put_contents($inputPath, Crypt::encryptString(json_encode($input, JSON_THROW_ON_ERROR)));
    DB::beginTransaction();

    try {
        $lock();
        foreach (range(1, 2) as $index) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/account-concurrency.php'), $mode, $inputPath, (string) $index], base_path(), timeout: 30);
            $process->start();
            $processes[] = $process;
        }
        // Prove both requests overlap and wait on PostgreSQL locks before releasing them.
        $deadline = microtime(true) + 10;
        do {
            DB::select('SELECT pg_stat_clear_snapshot()');
            $waiting = DB::selectOne("SELECT count(*) AS count FROM pg_stat_activity WHERE application_name = 'kody-account-concurrency' AND wait_event_type = 'Lock'")->count;
            if ($waiting >= 2) {
                break;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        expect((int) $waiting)->toBe(2);
        DB::commit();

        return array_map(function (Process $process): string {
            expect($process->wait())->toBe(0);

            return trim($process->getOutput());
        }, $processes);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop(0);
            }
        }
        if (is_file($inputPath)) {
            unlink($inputPath);
        }
    }
}

test('A01 simultaneous duplicate registrations create one account and one delivery', function () {
    $input = [
        'username' => 'racelearner', 'email' => 'race@example.test',
        'first_name' => 'Concurrent', 'last_name' => 'Learner',
        'password' => 'StrongPass12!', 'account_type' => 'learner',
    ];
    $results = simultaneousAccountRequests('register', $input, fn () => DB::statement('LOCK TABLE users IN SHARE MODE'));
    sort($results);
    expect($results)->toBe(['created', 'duplicate']);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('email_verifications', 1);
    $this->assertDatabaseCount('verification_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('A02 simultaneous resends cannot consume more than five requests', function () {
    $user = User::factory()->unverified()->create();
    app(EmailVerificationService::class)->request($user);
    EmailVerification::where('user_id', $user->id)->update(['request_count' => 4, 'last_requested_at' => now()->subMinutes(2)]);

    $results = simultaneousAccountRequests('resend', ['email' => $user->email], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['limited', 'queued'])
        ->and(EmailVerification::sole()->request_count)->toBe(5);
    $this->assertDatabaseCount('jobs', 2);
});

test('A02 simultaneous verification links activate the account only once', function () {
    $user = User::factory()->unverified()->create();
    app(EmailVerificationService::class)->request($user);
    $token = VerificationDelivery::sole()->token;
    $results = simultaneousAccountRequests('verify', ['token' => $token], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['invalid', 'verified'])
        ->and($user->fresh()->email_verified_at)->not->toBeNull()
        ->and(EmailVerification::sole()->token_hash)->toBeNull();
});

test('A03 overlapping password failures preserve the lockout threshold', function () {
    $user = User::factory()->create(['failed_login_attempts' => 2]);
    $results = simultaneousAccountRequests('login', ['email' => $user->email, 'password' => 'wrong'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    expect($results)->toBe(['locked', 'locked'])->and($user->fresh()->failed_login_attempts)->toBe(3)
        ->and($user->fresh()->login_locked_until)->not->toBeNull();
});

test('A03 overlapping valid logins authenticate one session and prompt the other', function () {
    $user = User::factory()->create();
    $results = simultaneousAccountRequests('login', ['email' => $user->email, 'password' => 'password'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['authenticated', 'conflict'])->and($user->fresh()->active_session_hash)->not->toBeNull();
});

test('A03 concurrent confirmations cannot both replace the same session', function () {
    $user = User::factory()->create([
        'active_session_hash' => hash('sha256', 'previous-session'),
        'active_session_expires_at' => now()->addMinutes(30),
    ]);
    $input = ['confirmation' => [
        'user_id' => $user->id, 'password_digest' => hash('sha256', $user->password),
        'session_hash' => $user->active_session_hash, 'expires_at' => now()->addMinutes(5)->timestamp,
    ]];
    $results = simultaneousAccountRequests('confirm', $input, fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['authenticated', 'invalid'])->and($user->fresh()->active_session_hash)->not->toBe(hash('sha256', 'previous-session'));
});
