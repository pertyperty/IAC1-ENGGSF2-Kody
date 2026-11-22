<?php

use App\Enums\Role;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\SampleCodeRunner;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sample runs fail closed with Judge0 disabled or with no runner binding', function () {
    $challenge = challengeFixture(true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $url = route('challenge-sample-runs.store', $challenge);
    config(['judge0.enabled' => true]);
    $this->postJson($url)->assertServiceUnavailable();
    $this->mock(SampleCodeRunner::class)->shouldNotReceive('run');
    config(['judge0.enabled' => false]);
    $this->postJson($url)->assertServiceUnavailable();
    $this->assertDatabaseCount('challenge_participations', 0);
});

test('sample runs send only approved public cases to a fake runner and use no graded attempts', function () {
    Http::fake();
    Queue::fake();
    config(['judge0.enabled' => true]);
    $challenge = challengeFixture(true, overrides: ['test_cases' => [
        ['input' => "2 3\n", 'expected_output' => "5\n", 'hidden' => false],
        ['input' => 'private input', 'expected_output' => 'private output', 'hidden' => true],
        ['input' => "0 2\n", 'expected_output' => "2\n", 'hidden' => false],
    ]]);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $runner = $this->mock(SampleCodeRunner::class);
    $url = route('challenge-sample-runs.store', $challenge);
    $payload = ['revision_id' => $challenge->published_revision_id, 'language' => 'python', 'source_code' => "  print(5)\n"];
    $this->postJson($url, $payload)->assertForbidden();
    $this->get(route('challenges.show', $challenge))->assertOk();
    $this->postJson($url, ['revision_id' => 1 + $challenge->published_revision_id] + $payload)->assertConflict();
    $this->postJson($url, ['language' => 'cpp'] + $payload)->assertUnprocessable();
    $this->postJson($url, ['source_code' => str_repeat('x', 65537)] + $payload)->assertUnprocessable();

    $runner->shouldReceive('run')->once()->with("  print(5)\n", 'python', 1000, 262144, [
        ['position' => 1, 'input' => "2 3\n", 'expected_output' => "5\n"],
        ['position' => 3, 'input' => "0 2\n", 'expected_output' => "2\n"],
    ])->andReturn([true, false]);
    $this->postJson($url, $payload)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson(['samples' => [['position' => 1, 'passed' => true], ['position' => 3, 'passed' => false]]]);
    $this->assertDatabaseCount('challenge_participations', 0);
    $this->assertDatabaseCount('challenge_submissions', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
});

test('starter code preserves exact spacing and is private until its revision is approved', function () {
    $author = moduleAccount(Role::Contributor);
    moduleSignIn($this, $author);
    $starter = "\n    # <script>placeholder</script>\n\n";
    $this->postJson(route('challenges.store'), challengeData(['starter_code' => str_repeat('é', 33000)]))->assertUnprocessable();
    $this->post(route('challenges.store'), challengeData(['starter_code' => $starter]))->assertRedirect();
    $revision = CodingChallengeRevision::sole();
    expect($revision->starter_code)->toBe($starter);
    $challenge = $revision->challenge;
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('challenges.show', $challenge))->assertNotFound();
    moduleSignIn($this, $author);
    $sessionId = session()->getId();
    $publishing = app(ChallengePublishing::class);
    $publishing->submit($author, $sessionId, $challenge, 1);
    $publishing->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 2, 'Approved', null);
    $publishing->save($author, $sessionId, challengeData(['record_version' => 3, 'starter_code' => 'A private replacement']), $challenge);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('challenges.show', $challenge))->assertOk()->assertSee('# &lt;script&gt;placeholder&lt;/script&gt;', false)
        ->assertDontSee('<script>placeholder</script>', false)->assertDontSee('A private replacement');
    expect(fn () => DB::transaction(fn () => $revision->update(['starter_code' => 'Mutated'])))->toThrow(QueryException::class);
    expect($revision->fresh()->starter_code)->toBe($starter);
});
