<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\WeeklyEvent;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
});

afterEach(function () {
    // Isolated kody_test database only: remove test history before guarded down.
    // Production rollback must never erase/mix weekly and standard attempts.
    $this->artisan('migrate:fresh')->assertSuccessful();
});

test('E02 two Moderators configuring the same week commit one event and one audit', function () {
    $challenge = challengeFixture(true);
    $actors = [moduleAccount(Role::Moderator)->id, moduleAccount(Role::Moderator)->id];
    $results = simultaneousAccountRequests('weekly-configure', ['actor_ids' => $actors, 'data' => weeklyData($challenge, ['week' => '2026-10-11']), 'clock' => now()->toIso8601String()],
        fn () => DB::table('weekly_calendar_locks')->where('id', 1)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['configured', 'duplicate']);
    $this->assertDatabaseCount('weekly_events', 1);
    expect(DB::table('audit_events')->where('event', 'weekly.configured')->count())->toBe(1);
});

test('E02 overlapping scheduler runs select and activate one weekly event', function () {
    challengeFixture(true);
    $results = simultaneousAccountRequests('weekly-sync', ['clock' => now()->toIso8601String()],
        fn () => DB::table('weekly_calendar_locks')->where('id', 1)->lockForUpdate()->first());
    expect($results)->toBe(['synchronized', 'synchronized']);
    $this->assertDatabaseCount('weekly_events', 1);
    expect(WeeklyEvent::sole()->status)->toBe('Active');
    expect(DB::table('audit_events')->where('event', 'weekly.auto-selected')->count())->toBe(1);
});

test('weekly overlapping final attempts consume only one slot and retries share the same confirmation', function (bool $distinct) {
    readyJudge();
    $challenge = challengeFixture(true);
    $event = weeklyFixture($challenge);
    $user = moduleAccount(Role::Learner);
    for ($i = 0; $i < 2; $i++) {
        evaluateAttempt(submitWeekly($event, $user));
    }
    $results = simultaneousAccountRequests('weekly-attempt', ['actor_id' => $user->id, 'event_id' => $event->id, 'challenge_id' => $challenge->id,
        'data' => attemptData($challenge), 'distinct' => $distinct, 'judge_config' => judgeConfiguration(), 'clock' => now()->toIso8601String()],
        fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe($distinct ? ['attempted', 'duplicate'] : ['attempted', 'attempted']);
    $this->assertDatabaseCount('challenge_submissions', 3);
    expect(DB::table('challenge_participations')->where('weekly_event_id', $event->id)->value('attempts'))->toBe(3);
    expect(DB::table('challenge_submissions')->whereIn('status', ['Queued', 'Evaluating'])->count())->toBe(1);
    $this->assertDatabaseCount('jobs', 3);
})->with([true, false]);
