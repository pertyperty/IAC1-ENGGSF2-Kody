<?php

use App\Enums\Role;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Gamification\WeeklyEvents;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function weeklyData(CodingChallenge $challenge, array $overrides = []): array
{
    return array_replace(['week' => app(WeeklyEvents::class)->weekStart()->toDateString(), 'challenge_id' => $challenge->id,
        'revision_id' => $challenge->published_revision_id, 'record_version' => 0, 'rules' => 'Three fresh attempts this week.', 'confirmed' => true], $overrides);
}

function weeklyFixture(?CodingChallenge $challenge = null, array $overrides = []): WeeklyEvent
{
    return app(WeeklyEvents::class)->configure(moduleAccount(Role::Moderator), 'module-test-session', weeklyData($challenge ?? challengeFixture(true), $overrides));
}

function submitWeekly(WeeklyEvent $event, User $user, array $overrides = []): ChallengeSubmission
{
    $challenge = CodingChallenge::find($event->challenge_id);

    return app(ChallengeSubmissions::class)->submit($user, 'module-test-session', $challenge,
        attemptData($challenge, array_replace(['revision_id' => $event->revision_id], $overrides)), $event);
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
    Http::preventStrayRequests();
});

test('E02 Moderators configure future pinned weekly quests with UTC windows and server-owned capped rewards', function () {
    $challenge = challengeFixture(true, overrides: ['test_cases' => [['input' => 'weekly-private-input', 'expected_output' => 'weekly-private-output', 'hidden' => true]]]);
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->get(route('weekly-studio.index'))->assertOk()->assertSee('A fresh quest')->assertDontSee('weekly-private-input')->assertDontSee('weekly-private-output')->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('weekly-studio.store'), weeklyData($challenge, ['week' => '2026-10-11', 'configured_by' => 999, 'reward_mode' => 'Paid', 'status' => 'Active']))->assertRedirect();
    $event = WeeklyEvent::sole();
    expect($event->starts_at->utc()->format('Y-m-d H:i:s'))->toBe('2026-10-10 16:00:00')
        ->and($event->ends_at->utc()->format('Y-m-d H:i:s'))->toBe('2026-10-17 16:00:00')
        ->and($event->status)->toBe('Scheduled')->and($event->reward_mode)->toBe('Capped')->and($event->economy_policy_version)->toBe(1)->and($event->revision_id)->toBe($challenge->published_revision_id);
    expect(DB::table('audit_events')->where('event', 'weekly.configured')->count())->toBe(1);
});

test('E02 all nonmoderator roles and guests cannot configure weekly events', function (Role $role) {
    $challenge = challengeFixture(true);
    $this->get(route('weekly-studio.index'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('weekly-studio.index'))->assertForbidden();
    $this->postJson(route('weekly-studio.store'), weeklyData($challenge))->assertForbidden();
    $this->assertDatabaseCount('weekly_events', 0);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Administrator]);

test('E02 invalid dates incomplete confirmation and malformed rules do not create events', function (array $overrides) {
    $challenge = challengeFixture(true);
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->postJson(route('weekly-studio.store'), weeklyData($challenge, $overrides))->assertUnprocessable();
    $this->assertDatabaseCount('weekly_events', 0);
})->with([[['week' => '2026-10-05']], [['week' => '2026-09-27']], [['week' => 'invalid']], [['week' => '2026-02-30']],
    [['confirmed' => false]], [['rules' => "bad\0rules"]], [['rules' => '']], [['rules' => str_repeat('a', 20001)]]]);

test('E02 future replacements reject stale versions and started events stay immutable', function () {
    $first = challengeFixture(true);
    $second = challengeFixture(true, overrides: ['title' => 'Another picnic']);
    $event = weeklyFixture($first, ['week' => '2026-10-11']);
    $moderator = moduleAccount(Role::Moderator);
    $service = app(WeeklyEvents::class);
    expect(fn () => $service->configure($moderator, 'module-test-session', weeklyData($second, ['week' => '2026-10-11'])))->toThrow(ValidationException::class);
    $service->configure($moderator, 'module-test-session', weeklyData($second, ['week' => '2026-10-11', 'record_version' => 1]));
    expect($event->fresh()->revision_id)->toBe($second->published_revision_id)->and($event->fresh()->record_version)->toBe(2);
    $this->travelTo(CarbonImmutable::parse('2026-10-11 00:00:00', 'Asia/Manila')->utc());
    $moderator->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => $service->configure($moderator, 'module-test-session', weeklyData($first, ['week' => '2026-10-11', 'record_version' => 2])))->toThrow(ValidationException::class);
});

test('E02 approved future planning preserves exactly one active event', function () {
    $challenge = challengeFixture(true);
    weeklyFixture($challenge);
    $future = app(WeeklyEvents::class)->configure(moduleAccount(Role::Moderator), 'module-test-session', weeklyData($challenge, ['week' => '2026-10-11']));
    expect($future->status)->toBe('Scheduled')->and(WeeklyEvent::where('status', 'Active')->count())->toBe(1);
    expect(WeeklyEvent::open()->count())->toBe(1);
    $this->assertDatabaseCount('weekly_events', 2);
});

test('E02 scheduler selects approved content once and changes weeks exactly at Manila midnight', function () {
    $challenge = challengeFixture(true);
    challengeFixture();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $event = WeeklyEvent::sole();
    expect($event->challenge_id)->toBe($challenge->id)->and($event->status)->toBe('Active');
    expect(DB::table('audit_events')->where('event', 'weekly.auto-selected')->sole()->actor_id)->toBeNull();
    $this->travelTo(CarbonImmutable::parse('2026-10-10 23:59:59', 'Asia/Manila')->utc());
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $this->assertDatabaseCount('weekly_events', 1);
    $this->travel(1)->seconds();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $this->assertDatabaseCount('weekly_events', 2);
    expect($event->fresh()->status)->toBe('Ended')->and(WeeklyEvent::where('status', 'Active')->count())->toBe(1);
});

test('E02 scheduler leaves an empty catalog without an invented event', function () {
    challengeFixture();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $this->assertDatabaseCount('weekly_events', 0);
    Http::assertNothingSent();
});

test('E02 pinned scheduled selection takes precedence over automatic selection', function () {
    $first = challengeFixture(true);
    $second = challengeFixture(true);
    $event = weeklyFixture($second, ['week' => '2026-10-11']);
    $this->travelTo($event->starts_at);
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    expect($event->fresh()->status)->toBe('Active')->and($event->fresh()->challenge_id)->toBe($second->id);
    expect(DB::table('audit_events')->where('event', 'weekly.activated')->count())->toBe(1);
    expect(DB::table('audit_events')->where('event', 'weekly.auto-selected')->count())->toBe(0);
});

test('weekly admission includes the opening second and excludes the closing second', function () {
    readyJudge();
    $event = weeklyFixture(overrides: ['week' => '2026-10-11']);
    $this->travelTo($event->starts_at->subSecond());
    $user = moduleAccount(Role::Learner);
    expect(fn () => submitWeekly($event, $user))->toThrow(ValidationException::class);
    $this->travel(1)->seconds();
    $submission = submitWeekly($event, $user);
    evaluateAttempt($submission);
    expect($submission->weekly_event_id)->toBe($event->id)->and($event->fresh()->status)->toBe('Active');
    $this->travelTo($event->ends_at);
    $user->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => submitWeekly($event, $user))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('challenge_submissions', 1);
});

test('weekly budgets stay independent from standard completion publications and previous weeks', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    for ($i = 0; $i < 3; $i++) {
        evaluateAttempt(submitAttempt($challenge, $user));
    }
    $event = weeklyFixture($challenge);
    for ($i = 0; $i < 3; $i++) {
        evaluateAttempt(submitWeekly($event, $user));
    }
    expect(fn () => submitWeekly($event, $user))->toThrow(ValidationException::class);
    expect(fn () => submitAttempt($challenge, $user))->toThrow(ValidationException::class);
    $this->travelTo($event->ends_at);
    $user->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $next = WeeklyEvent::open()->sole();
    expect(submitWeekly($next, $user)->attempt)->toBe(1);
    expect(DB::table('challenge_participations')->whereNull('weekly_event_id')->value('attempts'))->toBe(3);
    $this->assertDatabaseCount('challenge_participations', 3);
});

test('weekly revision pin survives published replacements and ignores client-supplied event identities', function () {
    readyJudge();
    $challenge = challengeFixture(true, overrides: ['test_cases' => [['input' => 'weekly-private-input', 'expected_output' => 'weekly-private-output', 'hidden' => true]]]);
    $event = weeklyFixture($challenge);
    $author = User::find($challenge->created_by);
    app(ChallengePublishing::class)->save($author, 'module-test-session', challengeData(['record_version' => 3, 'title' => 'New published puzzle']), $challenge);
    app(ChallengePublishing::class)->submit($author, 'module-test-session', $challenge->fresh(), 4);
    app(ChallengePublishing::class)->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 5, 'Approved', null);
    $user = moduleAccount(Role::Learner);
    $submission = submitWeekly($event, $user);
    expect($submission->revision_id)->toBe($event->revision_id)->not->toBe($challenge->fresh()->published_revision_id);
    moduleSignIn($this, $user);
    $this->get(route('weekly-events.show', $event))->assertOk()->assertSee('Picnic sums')->assertDontSee('New published puzzle')->assertDontSee('weekly-private-input')->assertDontSee('weekly-private-output');
    $this->postJson(route('weekly-events.attempt', $event), attemptData($challenge->fresh(), ['weekly_event_id' => $event->id]))->assertUnprocessable();
});

test('weekly confirmations are idempotent within their context and active standard work does not consume the weekly slot', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $event = weeklyFixture($challenge);
    $user = moduleAccount(Role::Learner);
    $standard = submitAttempt($challenge, $user);
    $data = ['confirmation_id' => $standard->confirmation_id];
    $weekly = submitWeekly($event, $user, $data);
    expect(submitWeekly($event, $user, $data)->id)->toBe($weekly->id);
    expect(fn () => submitWeekly($event, $user))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('challenge_submissions', 2);
    $this->assertDatabaseCount('jobs', 2);
});

test('committed weekly evaluations continue after closing and private history keeps the correct week', function () {
    readyJudge();
    $event = weeklyFixture();
    $this->travelTo($event->ends_at->subSecond());
    $user = moduleAccount(Role::Learner);
    $submission = submitWeekly($event, $user);
    $this->travel(2)->seconds();
    evaluateAttempt($submission);
    moduleSignIn($this, $user);
    $this->get(route('challenge-attempts.show', $submission))->assertOk()->assertSee('Weekly quest')->assertSee('Oct 4, 2026');
    $this->get(route('weekly-events.index'))->assertOk()->assertSee('Picnic sums');
    $this->get(route('weekly-events.show', $event))->assertNotFound();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('weekly-events.index'))->assertDontSee('Picnic sums');
    $this->get(route('challenge-attempts.show', $submission))->assertForbidden();
});

test('archived challenges stop new weekly access and scheduler records unavailability once', function () {
    $challenge = challengeFixture(true);
    $event = weeklyFixture($challenge);
    app(ChallengePublishing::class)->archive(User::find($challenge->created_by), 'module-test-session', $challenge, 3);
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    $this->artisan('kody:weekly-events-sync')->assertSuccessful();
    expect($event->fresh()->status)->toBe('Unavailable');
    expect(DB::table('audit_events')->where('event', 'weekly.unavailable')->count())->toBe(1);
    moduleSignIn($this, User::factory()->create());
    $this->get(route('weekly-events.show', $event))->assertNotFound();
    $this->get(route('dashboard'))->assertDontSee("Explore this week's quest");
});

test('disabled Judge0 still allows weekly configuration and previews without consuming an attempt', function () {
    $event = weeklyFixture();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('dashboard'))->assertOk()->assertSee('THIS WEEK')->assertSee('Picnic sums');
    $this->get(route('weekly-events.show', $event))->assertOk()->assertSee('Code evaluation is not available yet')->assertSee('25 bonus XP')->assertSee('monthly reward budget');
    $this->postJson(route('weekly-events.attempt', $event), attemptData(CodingChallenge::find($event->challenge_id)))->assertUnprocessable();
    $this->assertDatabaseCount('challenge_participations', 0);
    Http::assertNothingSent();
});

test('E02 fresh role session and audit failure prevent partial schedule writes', function () {
    $challenge = challengeFixture(true);
    $moderator = moduleAccount(Role::Moderator);
    User::whereKey($moderator->id)->update(['account_role' => Role::Learner]);
    expect(fn () => app(WeeklyEvents::class)->configure($moderator, 'module-test-session', weeklyData($challenge)))->toThrow(AuthorizationException::class);
    User::whereKey($moderator->id)->update(['account_role' => Role::Moderator, 'active_session_hash' => hash('sha256', 'revoked')]);
    expect(fn () => app(WeeklyEvents::class)->configure($moderator, 'module-test-session', weeklyData($challenge)))->toThrow(AuthorizationException::class);
    User::whereKey($moderator->id)->update(['active_session_hash' => hash('sha256', 'module-test-session')]);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->andThrow(new RuntimeException('Audit unavailable')));
    expect(fn () => app(WeeklyEvents::class)->configure($moderator, 'module-test-session', weeklyData($challenge)))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('weekly_events', 0);
});

test('weekly mutation routes enforce CSRF and canonical event identifiers', function () {
    $event = weeklyFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->app['env'] = 'local';
    $this->post(route('weekly-studio.store'), weeklyData(CodingChallenge::find($event->challenge_id)))->assertStatus(419);
    $this->post(route('weekly-events.attempt', $event), [])->assertStatus(419);
    $this->get('/weekly/invalid-id')->assertNotFound();
});

test('weekly PostgreSQL checks protect Sunday windows approved reward modes and context binding', function () {
    readyJudge();
    $event = weeklyFixture();
    $submission = submitAttempt(CodingChallenge::find($event->challenge_id), moduleAccount(Role::Learner));
    expect(fn () => DB::transaction(fn () => $event->update(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(8)])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => WeeklyEvent::whereKey($event->id)->update(['reward_mode' => 'Paid'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => $submission->update(['weekly_event_id' => $event->id])))->toThrow(QueryException::class);
});

test('weekly schema rollback refuses existing weekly history instead of merging budgets', function () {
    readyJudge();
    $event = weeklyFixture();
    $submission = submitWeekly($event, moduleAccount(Role::Learner));
    $migration = require database_path('migrations/2026_10_03_000013_add_weekly_challenge_events.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Weekly participation history must be preserved.');
    expect($submission->fresh()->weekly_event_id)->toBe($event->id);
    $this->assertDatabaseCount('challenge_participations', 1);
});
