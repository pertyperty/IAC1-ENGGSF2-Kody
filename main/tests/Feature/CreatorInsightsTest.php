<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Engagement\ContentFeedback;
use App\Services\Engagement\CreatorInsights;
use App\Services\Gamification\LearningProgression;
use App\Services\Transactions\ContentAccess;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('creator insights count only owned content and keep private participant data out of charts', function () {
    $module = moduleFixture(true);
    $other = moduleFixture(true);
    $creator = User::findOrFail($module->created_by);
    $student = moduleAccount(Role::Learner);
    foreach ([$module, $other] as $lesson) {
        app(ContentAccess::class)->open($student, 'module-test-session', 'module', $lesson->id);
        app(ContentFeedback::class)->read($student, 'module-test-session', 'module', $lesson->id, true);
        app(LearningProgression::class)->recordModule($student, 'module-test-session', $lesson->id, $lesson->published_revision_id, 'game', courseWin());
    }
    $result = app(CreatorInsights::class)->snapshot($creator, 'module-test-session', 7);
    expect($result['metrics'][0]['value'])->toBe(1)->and($result['metrics'][1]['value'])->toBe(1);
    expect(array_sum(array_column($result['charts'][0]['points'], 'value')))->toBe(1);
    expect($result['charts'][0]['points'])->toHaveCount(7);
    expect(json_encode($result))->not->toContain($student->email, $student->username, $other->publishedRevision->title);
    moduleSignIn($this, $creator);
    $this->get(route('dashboard', ['insight_days' => 90]))->assertOk()->assertSee('See what your learners discover')->assertSee('View daily totals');
    $this->getJson(route('dashboard', ['insight_days' => 400]))->assertUnprocessable();
});

test('creator chart days use Manila dates across the UTC midnight boundary', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-12 00:30:00', 'Asia/Manila'));
    $module = moduleFixture(true);
    $creator = User::findOrFail($module->created_by);
    $student = moduleAccount(Role::Learner);
    app(ContentAccess::class)->open($student, 'module-test-session', 'module', $module->id);
    app(ContentFeedback::class)->read($student, 'module-test-session', 'module', $module->id, true);
    $result = app(CreatorInsights::class)->snapshot($creator, 'module-test-session', 7);
    expect($result['charts'][0]['points'][6])->toMatchArray(['date' => '2026-10-12', 'value' => 1]);
});

test('Contributors receive quest metrics and noncreator accounts cannot inspect creator reports', function () {
    $creator = moduleAccount(Role::Contributor);
    $result = app(CreatorInsights::class)->snapshot($creator, 'module-test-session', 30);
    expect($result['metrics'][0]['label'])->toBe('Quest participants')->and($result['metrics'][3]['value'])->toBe('No results');
    foreach ([Role::Learner, Role::Moderator, Role::Administrator] as $role) {
        expect(fn () => app(CreatorInsights::class)->snapshot(moduleAccount($role), 'module-test-session', 7))->toThrow(AuthorizationException::class);
    }
});

test('quest insights separate pending attempts from terminal pass rates and exclude other creators', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $other = challengeFixture(true);
    $creator = User::findOrFail($challenge->created_by);
    foreach (['Passed', 'Failed', 'Queued'] as $status) {
        $submission = submitAttempt($challenge, moduleAccount(Role::Learner));
        if ($status !== 'Queued') {
            DB::table('challenge_submissions')->where('id', $submission->id)->update([
                'status' => $status, 'passed_cases' => $status === 'Passed' ? $submission->total_cases : 0, 'completed_at' => now(),
            ]);
        }
    }
    submitAttempt($other, moduleAccount(Role::Learner));
    $result = app(CreatorInsights::class)->snapshot($creator, 'module-test-session', 7);
    expect(array_column($result['metrics'], 'value'))->toBe([3, 3, 1, '50%']);
    expect(array_sum(array_column($result['charts'][0]['points'], 'value')))->toBe(3);
    expect(array_sum(array_column($result['charts'][1]['points'], 'value')))->toBe(1);
});
