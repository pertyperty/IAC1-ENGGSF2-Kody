<?php

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(RefreshDatabase::class);

function signInForLearning(TestCase $test): User
{
    $user = User::factory()->create();
    $test->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $test->withCredentials()->withCookie(config('session.cookie'), session()->getId());

    return $user;
}

function sequenceSolution(): array
{
    return ['program' => ['right', 'right', 'up', 'right', 'right']];
}

test('approved ladder clears each game in order and saves real hub state', function () {
    $user = signInForLearning($this);
    $this->get(route('learning.show', 'loops'))->assertForbidden();
    $this->postJson(route('play.game', 'loops'), ['program' => ['right', 'up', 'right'], 'repeat' => true])->assertForbidden();
    $this->postJson(route('play.game', 'sequences'), sequenceSolution() + ['user_id' => 999, 'xp' => 100000, 'current_streak' => 999])->assertOk()
        ->assertJsonPath('progress.current_streak', 1)->assertJsonPath('progress.levels.loops.unlocked', true);
    $this->get(route('learning.show', 'loops'))->assertOk();
    $this->postJson(route('play.game', 'loops'), ['program' => ['right', 'up', 'right'], 'repeat' => true])->assertOk();
    $this->postJson(route('play.game', 'conditions'), sequenceSolution() + ['conditional' => true])->assertOk()->assertJsonPath('progress.completed_count', 3);
    $this->get(route('dashboard'))->assertOk()->assertSee('Cleared')->assertSee('Daily streak')->assertSee('Play again');
    $this->assertDatabaseCount('learning_level_completions', 3);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'current_streak' => 1]);
});

test('invalid game and quiz outcomes cannot award progress or skip levels', function () {
    signInForLearning($this);
    $this->postJson(route('play.game', 'sequences'), ['program' => ['up'], 'success' => true])->assertJsonValidationErrors('completion');
    $this->postJson(route('play.game', 'sequences'), ['program' => ['constructor']])->assertJsonValidationErrors('program.0');
    $this->postJson(route('play.game', 'sequences'), ['program' => ['step' => 'right']])->assertJsonValidationErrors('program');
    $this->postJson(route('play.quiz', 'sequences'), ['answer' => 'same'])->assertJsonValidationErrors('completion');
    $this->postJson(route('play.game', 'missing'), sequenceSolution())->assertNotFound();
    $this->assertDatabaseCount('learning_progress', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('quiz completion qualifies daily activity without clearing its game objective', function () {
    $user = signInForLearning($this);
    $this->postJson(route('play.quiz', 'sequences'), ['answer' => 'order'])->assertOk()->assertJsonPath('progress.current_streak', 1)
        ->assertJsonPath('progress.levels.loops.unlocked', false);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseHas('learning_activity_days', ['user_id' => $user->id, 'kind' => 'quiz']);
});

test('Manila midnight advances the streak once per day and missed days reset it', function () {
    $this->travelTo(now()->setDate(2026, 10, 3)->setTime(15, 59, 30));
    $user = signInForLearning($this);
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertOk()->assertJsonPath('progress.current_streak', 1);
    $this->travel(1)->minutes();
    $this->postJson(route('play.quiz', 'sequences'), ['answer' => 'order'])->assertOk()->assertJsonPath('progress.current_streak', 2);
    $this->postJson(route('play.quiz', 'sequences'), ['answer' => 'order'])->assertOk()->assertJsonPath('progress.current_streak', 2);
    $this->assertDatabaseCount('learning_activity_days', 2);
    $this->travel(48)->hours();
    expect(app(LearningProgression::class)->snapshot($user->id)['current_streak'])->toBe(0);
    // The test session expires normally; sign in again before the next completion.
    $this->getJson(route('dashboard'))->assertUnauthorized();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertOk()->assertJsonPath('progress.current_streak', 1)->assertJsonPath('progress.longest_streak', 2);
});

test('completion retries are idempotent and another learner cannot choose the owner', function () {
    $user = signInForLearning($this);
    $other = User::factory()->create();
    for ($i = 0; $i < 3; $i++) {
        $this->postJson(route('play.game', 'sequences'), sequenceSolution() + ['user_id' => $other->id])->assertOk();
    }
    $this->assertDatabaseCount('learning_level_completions', 1);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_progress', 1);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'current_streak' => 1]);
    expect(app(LearningProgression::class)->snapshot($other->id)['completed_count'])->toBe(0);
});

test('sensitive completion rechecks authorization after acquiring the account lock', function () {
    $user = signInForLearning($this);
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    expect(fn () => app(LearningProgression::class)->record($user, session()->getId(), 'sequences', 'game', sequenceSolution()))
        ->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('streak persistence failure rolls back activity and level unlocks', function () {
    $user = signInForLearning($this);
    DB::listen(function (QueryExecuted $query): void {
        if (str_starts_with($query->sql, 'insert into "learning_progress"')) {
            throw new RuntimeException('Test streak persistence failure.');
        }
    });
    expect(fn () => app(LearningProgression::class)->record($user, session()->getId(), 'sequences', 'game', sequenceSolution()))
        ->toThrow(RuntimeException::class, 'Test streak persistence failure.');
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseCount('learning_progress', 0);
});

test('progression endpoints require authentication, CSRF and bounded input', function () {
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertUnauthorized();
    signInForLearning($this);
    $this->postJson(route('play.game', 'sequences'), ['program' => array_fill(0, 13, 'right')])->assertJsonValidationErrors('program');
    $this->app['env'] = 'production';
    $this->post(route('play.game', 'sequences'), sequenceSolution())->assertStatus(419);
});

test('PostgreSQL prevents negative streaks and duplicate level completion', function () {
    $user = signInForLearning($this);
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertOk();
    expect(fn () => DB::transaction(fn () => DB::table('learning_progress')->where('user_id', $user->id)->update(['current_streak' => -1])))
        ->toThrow(QueryException::class);
    $completion = (array) DB::table('learning_level_completions')->first();
    $completion['id'] = (string) Str::uuid();
    expect(fn () => DB::transaction(fn () => DB::table('learning_level_completions')->insert($completion)))->toThrow(QueryException::class);
});
