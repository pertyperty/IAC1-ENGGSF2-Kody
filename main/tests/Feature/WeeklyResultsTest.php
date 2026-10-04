<?php

use App\Enums\Role;
use App\Services\Gamification\WeeklyResults;
use App\Services\Transactions\WalletLedger;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function closedEconomyWeek(array $scores = [10000, 10000, 7500]): array
{
    $event = weeklyFixture();
    $users = [];
    foreach ($scores as $score) {
        $user = moduleAccount(Role::Learner);
        $users[] = $user;
        // Isolated domain fixture: production records are captured only by evaluator completion.
        DB::table('weekly_attempt_scores')->insert(['id' => (string) Str::uuid(), 'event_id' => $event->id, 'user_id' => $user->id, 'score' => $score, 'completed_at' => now()]);
    }
    $thisEventEnd = $event->ends_at;
    CarbonImmutable::setTestNow($thisEventEnd);
    Carbon::setTestNow($thisEventEnd);
    DB::table('weekly_events')->where('id', $event->id)->update(['status' => 'Ended']);

    return [$event->fresh(), $users];
}

test('E05 E06 best attempts and competition ties publish once within purchased-spend and cash caps', function () {
    Queue::fake();
    [$event, $players] = closedEconomyWeek();
    DB::table('weekly_attempt_scores')->insert(['id' => (string) Str::uuid(), 'event_id' => $event->id, 'user_id' => $players[0]->id, 'score' => 1000, 'completed_at' => now()]);
    $buyer = moduleAccount(Role::Learner);
    $creator = moduleAccount(Role::Instructor);
    economyCredit($buyer, 1000, 100000);
    DB::transaction(fn () => app(WalletLedger::class)->buy($buyer->id, $creator->id, 'course', 1, 1, 1000, 'Cash'));
    DB::table('content_purchases')->update(['created_at' => now('Asia/Manila')->startOfMonth()->subMonth()->utc()]);
    $staff = moduleAccount(Role::Moderator);
    app(WeeklyResults::class)->publish($staff, 'module-test-session', $event->id, $event->record_version, true);
    app(WeeklyResults::class)->publish($staff, 'module-test-session', $event->id, $event->record_version, true);
    $rows = DB::table('weekly_results')->where('event_id', $event->id)->orderBy('user_id')->get();
    expect($rows->pluck('rank')->all())->toBe([1, 1, 3])->and($rows->pluck('reward_kb')->all())->toBe([8, 8, 0]);
    expect(DB::table('wallet_operations')->where('kind', 'Reward')->count())->toBe(2)->and(DB::table('reward_months')->value('issued_kb'))->toBe(16);
    expect(DB::table('audit_events')->where('event', 'weekly.results_published')->count())->toBe(1);
    DB::beginTransaction();
    try {
        DB::table('weekly_results')->where('event_id', $event->id)->update(['reward_kb' => 999]);
        $this->fail('Published rows must be immutable.');
    } catch (QueryException) {
        DB::rollBack();
    }
    DB::beginTransaction();
    try {
        DB::table('weekly_results')->insert(['event_id' => $event->id, 'user_id' => $buyer->id, 'score' => 10000, 'rank' => 1]);
        $this->fail('Late insert must fail.');
    } catch (QueryException) {
        DB::rollBack();
    }
});

test('E06 no historical spend produces zero rewards and legacy events keep their deferred contract', function (bool $legacy) {
    Queue::fake();
    [$event] = closedEconomyWeek([10000]);
    DB::transaction(fn () => app(WalletLedger::class)->cash('owner-funding:pilot', 100000));
    if ($legacy) {
        DB::table('weekly_events')->where('id', $event->id)->update(['reward_mode' => 'Deferred', 'economy_policy_version' => 0]);
    }
    app(WeeklyResults::class)->publish(moduleAccount(Role::Administrator), 'module-test-session', $event->id, $event->record_version, true);
    expect(DB::table('weekly_results')->value('reward_kb'))->toBe(0)->and(DB::table('wallet_operations')->where('kind', 'Reward')->count())->toBe(0);
})->with([false, true]);

test('E05 future windows stale versions and participant publication remain blocked', function () {
    Queue::fake();
    $event = weeklyFixture();
    expect(fn () => app(WeeklyResults::class)->prepare($event->id))->toThrow(ValidationException::class);
    $this->travelTo($event->ends_at);
    DB::table('weekly_events')->where('id', $event->id)->update(['status' => 'Ended']);
    [$ended] = closedEconomyWeek();
    expect(fn () => app(WeeklyResults::class)->publish(moduleAccount(Role::Moderator), 'module-test-session', $ended->id, 999, true))->toThrow(ValidationException::class);
    expect(fn () => app(WeeklyResults::class)->publish(moduleAccount(Role::Learner), 'module-test-session', $ended->id, $ended->record_version, true))->toThrow(HttpException::class);
    expect(DB::table('weekly_result_sets')->where('status', 'Published')->count())->toBe(0);
});

test('E06 ties crossing the prize cutoff share the complete occupied pool without dust favoritism', function () {
    $rows = [(object) ['user_id' => 1, 'rank' => 1, 'score' => 10000], (object) ['user_id' => 2, 'rank' => 2, 'score' => 10000],
        (object) ['user_id' => 3, 'rank' => 2, 'score' => 10000], (object) ['user_id' => 4, 'rank' => 2, 'score' => 10000]];
    expect(app(WeeklyResults::class)->shares($rows))->toBe([1 => 10, 2 => 3, 3 => 3, 4 => 3]);
});
