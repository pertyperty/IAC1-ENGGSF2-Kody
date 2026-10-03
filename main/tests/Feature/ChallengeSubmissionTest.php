<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeCaseEvaluation;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Challenges\Judge0\Judge0Client;
use App\Services\Challenges\Judge0\ProviderReadiness;
use App\Services\Challenges\Judge0\ProviderUnavailable;
use App\Services\Challenges\SubmissionEvaluation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function judgeConfiguration(): array
{
    return ['judge0.enabled' => true, 'judge0.url' => 'https://judge.example.test',
        'judge0.languages' => ['python' => 101, 'java' => 102, 'cpp' => 103]];
}

function judgeLimits(): array
{
    return ['max_cpu_time_limit' => 5, 'max_memory_limit' => 262144, 'max_wall_time_limit' => 20,
        'max_cpu_extra_time' => 2, 'max_stack_limit' => 128000, 'max_max_file_size' => 4096,
        'max_max_processes_and_or_threads' => 120, 'max_number_of_runs' => 20,
        'enable_network' => true, 'allow_enable_network' => true];
}

function readyJudge(): void
{
    config(judgeConfiguration());
    DB::table('judge0_profiles')->insert(['fingerprint' => app(Judge0Client::class)->fingerprint(),
        'languages' => json_encode(['python' => ['id' => 101], 'java' => ['id' => 102], 'cpp' => ['id' => 103]]),
        'limits' => json_encode(judgeLimits()), 'verified_at' => now()]);
}

function attemptData(CodingChallenge $challenge, array $overrides = []): array
{
    return array_replace(['revision_id' => $challenge->published_revision_id, 'language' => 'python',
        'source_code' => "print(sum(map(int, input().split())))\n", 'confirmation_id' => (string) Str::uuid(), 'confirmed' => true], $overrides);
}

function submitAttempt(CodingChallenge $challenge, User $user, array $overrides = []): ChallengeSubmission
{
    return app(ChallengeSubmissions::class)->submit($user, 'module-test-session', $challenge, attemptData($challenge, $overrides));
}

function evaluateAttempt(ChallengeSubmission $submission, int $status = 3): void
{
    Http::fake(fn ($request) => $request->method() === 'POST'
        ? Http::response(['token' => (string) Str::uuid()], 201)
        : Http::response(['status' => ['id' => $status], 'stdout' => 'HIDDEN_INPUT_SECRET']));
    for ($step = 0; $step < 6 && $submission->fresh()->completed_at === null; $step++) {
        app(SubmissionEvaluation::class)->advance($submission->id);
        if ($step === 0) {
            expect(ChallengeCaseEvaluation::where('submission_id', $submission->id)->orderBy('id')->first()->status)->toBe('Submitted');
        }
    }
}

beforeEach(function () {
    Http::preventStrayRequests();
});

test('C03 verified participants confirm free durable encrypted attempts queued atomically', function (Role $role) {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('challenges.show', $challenge))->assertOk()->assertSee('Submit my solution');
    $source = "  print('hello')\n\n";
    $this->post(route('challenge-attempts.store', $challenge), attemptData($challenge, ['source_code' => $source, 'status' => 'Passed', 'passed_cases' => 99]))->assertRedirect();
    $submission = ChallengeSubmission::sole();
    expect($submission->source_code)->toBe($source)->and($submission->status)->toBe('Queued')->and($submission->attempt)->toBe(1);
    expect(DB::table('challenge_submissions')->value('source_code'))->not->toContain('hello');
    expect($submission->toArray())->not->toHaveKey('source_code');
    $job = DB::table('jobs')->sole();
    expect($job->queue)->toBe('code-execution')->and($job->payload)->toContain($submission->id)->not->toContain('hello');
    $this->get(route('challenge-attempts.show', $submission))->assertOk()->assertSee('Your code is saved')->assertSee('hello')->assertHeader('Cache-Control', 'no-store, private');
    $this->getJson(route('challenge-attempts.status', $submission))->assertExactJson(['status' => 'Queued', 'passed_cases' => 0, 'total_cases' => 2, 'feedback' => null, 'completed' => false]);
    Http::assertNothingSent();
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('C03 blocked roles and guests cannot create attempts', function (Role $role) {
    readyJudge();
    $challenge = challengeFixture(true);
    $this->post(route('challenge-attempts.store', $challenge), attemptData($challenge))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->postJson(route('challenge-attempts.store', $challenge), attemptData($challenge))->assertForbidden();
    $this->assertDatabaseCount('challenge_submissions', 0);
})->with([Role::Moderator, Role::Administrator]);

test('C03 unavailable provider verification or limits never consume attempts', function (string $change) {
    readyJudge();
    $challenge = challengeFixture(true);
    if ($change === 'disabled') {
        config(['judge0.enabled' => false]);
    }
    if ($change === 'changed') {
        config(['judge0.languages.python' => 104]);
    }
    if ($change === 'unverified') {
        DB::table('judge0_profiles')->delete();
    }
    if ($change === 'limits') {
        DB::table('judge0_profiles')->update(['limits' => json_encode(['max_cpu_time_limit' => 1, 'max_memory_limit' => 128000])]);
    }
    expect(fn () => submitAttempt($challenge, moduleAccount(Role::Learner)))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('challenge_submissions', 0);
    $this->assertDatabaseCount('challenge_participations', 0);
    Http::assertNothingSent();
})->with(['disabled', 'changed', 'unverified', 'limits']);

test('C03 validation rejects unconfirmed unsupported oversized null and fabricated weekly requests', function (array $overrides) {
    readyJudge();
    $challenge = challengeFixture(true);
    moduleSignIn($this, User::factory()->create());
    $this->postJson(route('challenge-attempts.store', $challenge), attemptData($challenge, $overrides))->assertUnprocessable();
    $this->assertDatabaseCount('challenge_submissions', 0);
})->with([[['confirmed' => false]], [['language' => 'php']], [['source_code' => str_repeat('a', 65537)]],
    [['source_code' => "bad\0code"]], [['source_code' => '   ']], [['source_code' => ['invalid']]], [['weekly_event_id' => 1]], [['confirmation_id' => 'invalid']]]);

test('C03 retries reuse one attempt but cannot change confirmed code or overlap active evaluations', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $data = attemptData($challenge);
    $service = app(ChallengeSubmissions::class);
    $first = $service->submit($user, 'module-test-session', $challenge, $data);
    expect($service->submit($user, 'module-test-session', $challenge, $data)->id)->toBe($first->id);
    expect(fn () => $service->submit($user, 'module-test-session', $challenge, array_replace($data, ['source_code' => 'different'])))->toThrow(ValidationException::class);
    expect(fn () => submitAttempt($challenge, $user))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('jobs', 1);
    $this->assertDatabaseCount('challenge_submissions', 1);
});

test('C03 standard three attempt budget survives publication changes and archived results remain private', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $first = submitAttempt($challenge, $user);
    evaluateAttempt($first);
    $author = User::find($challenge->created_by);
    app(ChallengePublishing::class)->save($author, 'module-test-session', challengeData(['record_version' => 3]), $challenge);
    app(ChallengePublishing::class)->submit($author, 'module-test-session', $challenge->fresh(), 4);
    app(ChallengePublishing::class)->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 5, 'Approved', null);
    $challenge->refresh();
    expect($challenge->published_revision_id)->not->toBe($first->revision_id);
    expect(fn () => submitAttempt($challenge, $user, ['revision_id' => $first->revision_id]))->toThrow(ValidationException::class);
    for ($i = 0; $i < 2; $i++) {
        evaluateAttempt(submitAttempt($challenge, $user), 4);
    }
    expect(fn () => submitAttempt($challenge, $user))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('challenge_submissions', 3);
    app(ChallengePublishing::class)->archive($author, 'module-test-session', $challenge, 6);
    moduleSignIn($this, $user);
    $this->get(route('challenge-attempts.show', $first))->assertOk()->assertSee('Revision 1');
    $this->get(route('challenges.show', $challenge))->assertNotFound();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('challenge-attempts.show', $first))->assertForbidden();
    $this->getJson(route('challenge-attempts.status', $first))->assertForbidden();
});

test('C03 fresh session status and role are rechecked before consuming an attempt', function (string $change) {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $updates = match ($change) {
        'role' => ['account_role' => Role::Moderator], 'session' => ['active_session_hash' => hash('sha256', 'revoked')], default => ['account_status' => AccountStatus::Suspended]
    };
    User::whereKey($user->id)->update($updates);
    expect(fn () => submitAttempt($challenge, $user))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('challenge_submissions', 0);
})->with(['role', 'session', 'status']);

test('C03 queue failure rolls back source attempts case records and audit', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $queue = Mockery::mock();
    $queue->shouldReceive('pushOn')->andThrow(new RuntimeException('Queue unavailable.'));
    Queue::shouldReceive('connection')->with('database')->andReturn($queue);
    expect(fn () => submitAttempt($challenge, moduleAccount(Role::Learner)))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('challenge_submissions', 0);
    $this->assertDatabaseCount('challenge_participations', 0);
    $this->assertDatabaseCount('challenge_case_evaluations', 0);
    expect(DB::table('audit_events')->where('event', 'challenge.attempted')->count())->toBe(0);
});

test('C04 all pinned tests evaluate once with bounded isolated provider requests and no hidden output', function () {
    readyJudge();
    config(['judge0.rapidapi_key' => 'private-test-key', 'judge0.rapidapi_host' => 'judge.example.test']);
    DB::table('judge0_profiles')->update(['fingerprint' => app(Judge0Client::class)->fingerprint()]);
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $submission = submitAttempt($challenge, $user);
    app(ChallengePublishing::class)->archive(User::find($challenge->created_by), 'module-test-session', $challenge, 3);
    evaluateAttempt($submission);
    expect($submission->fresh()->status)->toBe('Passed')->and($submission->fresh()->passed_cases)->toBe(2);
    expect(app(SubmissionEvaluation::class)->advance($submission->id))->toBeNull();
    Http::assertSentCount(4);
    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['language_id'] === 101
        && $request['enable_network'] === false && $request['number_of_runs'] === 1 && $request['cpu_time_limit'] === 1
        && $request['memory_limit'] === 262144 && base64_decode($request['stdin']) === "4 8\n"
        && $request->hasHeader('X-RapidAPI-Key', 'private-test-key'));
    expect(DB::table('challenge_case_evaluations')->pluck('provider_token')->implode(' '))->not->toContain('HIDDEN_INPUT_SECRET');
    moduleSignIn($this, $user);
    $this->get(route('challenge-attempts.show', $submission))->assertDontSee('HIDDEN_INPUT_SECRET')->assertDontSee('private-test-key');
    $this->getJson(route('challenge-attempts.status', $submission))->assertJsonPath('status', 'Passed')->assertDontSee('provider_token');
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('C04 provider verdicts are deterministic and infrastructure errors never become failed solutions', function (int $status, string $outcome) {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    evaluateAttempt($submission, $status);
    expect($submission->fresh()->status)->toBe($outcome)->and($submission->fresh()->completed_at)->not->toBeNull();
    expect(DB::table('challenge_participations')->value('active_submission_id'))->toBeNull();
})->with([[3, 'Passed'], [4, 'Failed'], [5, 'Failed'], [6, 'Failed'], [12, 'Failed'], [13, 'Unavailable'], [14, 'Failed']]);

test('C04 an ambiguous creation failure never repeats a remote POST', function () {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    Http::fake(['*' => Http::failedConnection()]);
    app(SubmissionEvaluation::class)->advance($submission->id);
    expect($submission->fresh()->status)->toBe('Unavailable');
    app(SubmissionEvaluation::class)->advance($submission->id);
    Http::assertSentCount(1);
});

test('C04 lost creation response expired leases and overdue jobs release the active evaluation safely', function (string $mode) {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    if ($mode === 'crashed') {
        ChallengeCaseEvaluation::where('submission_id', $submission->id)->orderBy('id')->first()->update(['status' => 'Creating']);
        $submission->update(['lease_id' => (string) Str::uuid(), 'lease_expires_at' => now()->subSecond()]);
    } else {
        $submission->update(['submitted_at' => now()->subMinutes(16)]);
    }
    $this->artisan('kody:submissions-expire')->assertSuccessful();
    if ($mode === 'crashed') {
        app(SubmissionEvaluation::class)->advance($submission->id);
    }
    expect($submission->fresh()->status)->toBe('Unavailable');
    expect(DB::table('challenge_participations')->value('active_submission_id'))->toBeNull();
    Http::assertNothingSent();
})->with(['crashed', 'overdue']);

test('C04 duplicate workers cannot issue a second creation during an active lease', function () {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    $submission->update(['lease_id' => (string) Str::uuid(), 'lease_expires_at' => now()->addMinute()]);
    expect(app(SubmissionEvaluation::class)->advance($submission->id))->toBe(10);
    Http::assertNothingSent();
});

test('C04 the durable database queue job advances and releases until the private result completes', function () {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    Http::fake(fn ($request) => $request->method() === 'POST'
        ? Http::response(['token' => (string) Str::uuid()], 201)
        : Http::response(['status' => ['id' => 3]]));
    for ($step = 0; $step < 4; $step++) {
        $job = Queue::connection('database')->pop('code-execution');
        expect($job)->not->toBeNull();
        $job->fire();
    }
    expect($submission->fresh()->status)->toBe('Passed');
    $this->assertDatabaseCount('jobs', 0);
});

test('C04 polling retries known tokens without repeating creation and finishes after pending status', function () {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    $token = (string) Str::uuid();
    Http::fake(['judge.example.test/submissions?*' => fn () => Http::response(['token' => $token], 201),
        'judge.example.test/submissions/*' => Http::sequence()->pushStatus(503)->push(['status' => ['id' => 2]])->push(['status' => ['id' => 3]])->push(['status' => ['id' => 3]])]);
    for ($i = 0; $i < 6; $i++) {
        app(SubmissionEvaluation::class)->advance($submission->id);
    }
    expect($submission->fresh()->status)->toBe('Passed');
    Http::assertSentCount(6);
    expect(Http::recorded(fn ($request) => $request->method() === 'POST')->count())->toBe(2);
    expect(ChallengeCaseEvaluation::first()->toArray())->not->toHaveKey('provider_token');
});

test('Judge0 preflight verifies active compiler mappings and limits without executing code', function () {
    config(judgeConfiguration());
    Http::fake(['judge.example.test/languages/' => Http::response([['id' => 101, 'name' => 'Python (3.12)'], ['id' => 102, 'name' => 'Java (OpenJDK 21)'], ['id' => 103, 'name' => 'C++ (GCC 14)']]),
        'judge.example.test/config_info' => Http::response(judgeLimits())]);
    $this->artisan('kody:judge0-check')->assertSuccessful();
    $this->assertDatabaseCount('judge0_profiles', 1);
    Http::assertSentCount(2);
    Http::assertNotSent(fn ($request) => $request->method() !== 'GET');
});

test('Judge0 adapter creates and reads a valid isolated execution verdict', function () {
    config(judgeConfiguration());
    $token = (string) Str::uuid();
    Http::fake(['*' => Http::sequence()->push(['token' => $token], 201)->push(['status' => ['id' => 3]])]);
    expect(app(Judge0Client::class)->create('print(1)', 101, '', '1', 1000, 262144))->toBe($token);
    expect(app(Judge0Client::class)->result($token)->outcome())->toBe('Passed');
});

test('Judge0 malformed unsafe mismatched or excessive provider responses stay sanitized', function (string $mode) {
    config(judgeConfiguration());
    if ($mode === 'unsafe-url') {
        config(['judge0.url' => 'https://user@judge.example.test']);
    }
    $response = match ($mode) {
        'wrong-language' => [['id' => 101, 'name' => 'PHP (8.4)']], 'oversize' => str_repeat('private', 50000), default => 'not-json private provider secret'
    };
    Http::fake(['*' => Http::response($response, 200)]);
    expect(fn () => app(ProviderReadiness::class)->verify())->toThrow(ProviderUnavailable::class, 'Code evaluation is temporarily unavailable.');
    $this->assertDatabaseCount('judge0_profiles', 0);
})->with(['unsafe-url', 'wrong-language', 'oversize', 'malformed']);

test('C03 PostgreSQL constraints prevent cross revision test cases and an excessive attempt count', function () {
    readyJudge();
    $first = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    $other = challengeFixture(true);
    expect(fn () => DB::transaction(fn () => ChallengeCaseEvaluation::create(['submission_id' => $first->id, 'revision_id' => $first->revision_id, 'test_case_id' => $other->publishedRevision->testCases->first()->id])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('challenge_participations')->where('id', $first->participation_id)->update(['attempts' => 4])))->toThrow(QueryException::class);
});

test('C03 code submissions require CSRF and never flash source into session validation history', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    moduleSignIn($this, User::factory()->create());
    $this->post(route('challenge-attempts.store', $challenge), attemptData($challenge, ['confirmed' => false, 'source_code' => 'private code']))->assertSessionHasErrors('confirmed');
    expect(session()->getOldInput('source_code'))->toBeNull();
    $this->app['env'] = 'local';
    $this->post(route('challenge-attempts.store', $challenge), attemptData($challenge))->assertStatus(419);
    $this->get('/challenge-attempts/invalid-id')->assertNotFound();
    $this->assertDatabaseCount('challenge_submissions', 0);
});

test('C03 storage failures are sanitized and roll back every attempt record', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')
        ->andThrow(new QueryException('pgsql', 'private source sql', ['hidden payload'], new RuntimeException('private source failure'))));
    Log::shouldReceive('error')->once()->with('Submission storage write failed.', ['sqlstate' => 0]);
    try {
        submitAttempt($challenge, $user);
        $this->fail('Expected a sanitized failure.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Submission storage is temporarily unavailable.')->and($exception->getPrevious())->toBeNull();
    }
    $this->assertDatabaseCount('challenge_submissions', 0);
    $this->assertDatabaseCount('challenge_participations', 0);
});
