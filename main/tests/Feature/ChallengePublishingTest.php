<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeTestCase;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\ChallengePublishing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function challengeData(array $overrides = []): array
{
    return array_replace(['record_version' => 1, 'title' => 'Picnic sums', 'description' => 'Add two picnic basket counts.',
        'language' => 'python', 'difficulty' => 'Easy', 'rules' => 'The counts are nonnegative integers.',
        'input_format' => 'Two integers separated by a space.', 'output_format' => 'Print their sum on one line.',
        'cpu_time_ms' => 1000, 'memory_kib' => 262144,
        'test_cases' => [['input' => "2 3\n", 'expected_output' => "5\n", 'hidden' => false],
            ['input' => "4 8\n", 'expected_output' => "12\n", 'hidden' => true]]], $overrides);
}

function challengeFixture(bool $published = false, Role $role = Role::Contributor, array $overrides = []): CodingChallenge
{
    $author = moduleAccount($role);
    $publishing = app(ChallengePublishing::class);
    $challenge = $publishing->save($author, 'module-test-session', challengeData($overrides));
    if ($published) {
        $publishing->submit($author, 'module-test-session', $challenge, 1);
        $publishing->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 2, 'Approved', null);
    }

    return $challenge->fresh();
}

test('C01 Contributors and Instructors save complete owned immutable challenge drafts', function (Role $role) {
    $author = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $author);
    $this->get(route('challenges.create'))->assertOk()->assertSee('Turn a problem into possibility');
    $this->post(route('challenges.store'), challengeData(['created_by' => 999, 'status' => 'Published', 'published_revision_id' => 999]))->assertRedirect();
    $challenge = CodingChallenge::sole();
    expect($challenge->created_by)->toBe($author->id)->and($challenge->status)->toBe('Draft')->and($challenge->published_revision_id)->toBeNull();
    $revision = $challenge->latestRevision;
    expect($revision->language)->toBe('python')->and($revision->cpu_time_ms)->toBe(1000)->and($revision->testCases->pluck('position')->all())->toBe([1, 2]);
    $this->get(route('challenges.index'))->assertOk()->assertSee('Picnic sums');
    $this->get(route('challenges.edit', $challenge))->assertOk()->assertSee('Hidden')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('dashboard'))->assertSee(route('challenges.index'));
    $this->get(route('challenges.catalog'))->assertDontSee('Picnic sums');
})->with([Role::Contributor, Role::Instructor]);

test('C01 noncreator roles cannot author or read another creator test cases', function (Role $role) {
    $challenge = challengeFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('challenges.create'))->assertForbidden();
    $this->postJson(route('challenges.store'), challengeData())->assertForbidden();
    $this->get(route('challenges.edit', $challenge))->assertForbidden();
    $this->assertDatabaseCount('coding_challenges', 1);
})->with([Role::Learner, Role::Moderator, Role::Administrator]);

test('C01 validation rejects incomplete malformed unsafe and excessive challenge definitions', function (array $overrides) {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Contributor]));
    $this->postJson(route('challenges.store'), challengeData($overrides))->assertUnprocessable();
    $this->assertDatabaseCount('coding_challenges', 0);
    $this->assertDatabaseCount('challenge_test_cases', 0);
})->with([
    'title limit' => [['title' => str_repeat('x', 151)]],
    'missing problem' => [['description' => '']],
    'unsupported javascript' => [['language' => 'javascript']],
    'unsupported php' => [['language' => 'php']],
    'difficulty' => [['difficulty' => 'Legendary']],
    'CPU bound' => [['cpu_time_ms' => 5001]],
    'negative CPU' => [['cpu_time_ms' => -1]],
    'memory bound' => [['memory_kib' => 262145]],
    'no tests' => [['test_cases' => []]],
    'not a list' => [['test_cases' => ['quest' => ['input' => '', 'expected_output' => '', 'hidden' => true]]]],
    'too many tests' => [['test_cases' => array_fill(0, 21, ['input' => '', 'expected_output' => '', 'hidden' => true])]],
    'oversized test' => [['test_cases' => [['input' => str_repeat('x', 16385), 'expected_output' => '', 'hidden' => false]]]],
    'oversized blank input' => [['test_cases' => [['input' => str_repeat(' ', 16385), 'expected_output' => '', 'hidden' => false]]]],
    'oversized blank output' => [['test_cases' => [['input' => '', 'expected_output' => str_repeat("\n", 16385), 'hidden' => false]]]],
    'missing expected output' => [['test_cases' => [['input' => '1', 'hidden' => false]]]],
    'unknown fields' => [['test_cases' => [['input' => '', 'expected_output' => '', 'hidden' => false, 'reward' => 999]]]],
    'NUL test' => [['test_cases' => [['input' => "\0", 'expected_output' => '1', 'hidden' => false]]]],
    'contradictory outputs' => [['test_cases' => [['input' => '1', 'expected_output' => 'a', 'hidden' => false], ['input' => '1', 'expected_output' => 'b', 'hidden' => true]]]],
]);

test('C01 test case whitespace and intentional empty input or output survive HTTP normalization', function () {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Contributor]));
    $cases = [['input' => "\n 2 3 \n", 'expected_output' => " 5 \n", 'hidden' => false], ['input' => '', 'expected_output' => '', 'hidden' => true]];
    $this->post(route('challenges.store'), challengeData(['test_cases' => $cases]))->assertRedirect();
    $saved = CodingChallenge::sole()->latestRevision->testCases;
    expect($saved[0]->input)->toBe($cases[0]['input'])->and($saved[0]->expected_output)->toBe($cases[0]['expected_output']);
    expect($saved[1]->input)->toBe('')->and($saved[1]->expected_output)->toBe('');
    expect($saved[0]->toArray())->not->toHaveKey('input')->not->toHaveKey('expected_output');
    $this->get(route('challenges.edit', CodingChallenge::sole()))->assertOk()->assertSee("\n\n 2 3 \n", false);
});

test('C02 confirmed Moderator and Administrator decisions publish atomically and privately notify creators', function (Role $role) {
    $challenge = challengeFixture();
    app(ChallengePublishing::class)->submit(User::find($challenge->created_by), 'module-test-session', $challenge, 1);
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('challenge-reviews.index'))->assertOk()->assertSee('Picnic sums');
    $this->get(route('challenge-reviews.show', $challenge))->assertOk()->assertSee('12')->assertSee('Hidden');
    $this->postJson(route('challenge-reviews.review', $challenge), ['record_version' => 2, 'decision' => 'Approved'])->assertUnprocessable();
    $this->post(route('challenge-reviews.review', $challenge), ['record_version' => 2, 'decision' => 'Approved', 'confirmed' => true])->assertRedirect();
    expect($challenge->fresh()->status)->toBe('Published')->and($challenge->fresh()->record_version)->toBe(3);
    expect($challenge->fresh()->publishedRevision->review_status)->toBe('Approved');
    $this->assertDatabaseHas('audit_events', ['event' => 'challenge.reviewed']);
    $this->assertDatabaseHas('notifications', ['type' => 'challenge.reviewed', 'notifiable_id' => $challenge->created_by]);
    $this->postJson(route('challenge-reviews.review', $challenge), ['record_version' => 2, 'decision' => 'Approved', 'confirmed' => true])->assertUnprocessable();
    expect(DB::table('notifications')->where('type', 'challenge.reviewed')->count())->toBe(1);
    moduleSignIn($this, User::find($challenge->created_by));
    $this->get(route('notifications.index'))->assertOk()->assertSee(route('challenges.edit', $challenge));
})->with([Role::Moderator, Role::Administrator]);

test('C02 rejection requires a reason and leaves drafts private while allowing a corrected new revision', function () {
    $challenge = challengeFixture();
    $owner = User::find($challenge->created_by);
    $publishing = app(ChallengePublishing::class);
    $publishing->submit($owner, 'module-test-session', $challenge, 1);
    $reviewer = moduleAccount(Role::Moderator);
    expect(fn () => $publishing->review($reviewer, 'module-test-session', $challenge->fresh(), 2, 'Rejected', ''))->toThrow(ValidationException::class);
    $publishing->review($reviewer, 'module-test-session', $challenge->fresh(), 2, 'Rejected', '<script>Improve the examples</script>');
    expect($challenge->fresh()->status)->toBe('Draft')->and($challenge->fresh()->published_revision_id)->toBeNull();
    moduleSignIn($this, $owner);
    $this->get(route('challenges.edit', $challenge))->assertSee('&lt;script&gt;Improve the examples&lt;/script&gt;', false)->assertDontSee('<script>Improve', false);
    $this->put(route('challenges.update', $challenge), challengeData(['record_version' => 3]))->assertRedirect();
    expect($challenge->fresh()->latestRevision->review_status)->toBe('Draft')->and($challenge->fresh()->latestRevision->number)->toBe(2);
});

test('C05 editing and rejecting replacements preserve the approved problem and exact test snapshot', function () {
    $challenge = challengeFixture(true);
    $oldRevision = $challenge->published_revision_id;
    $owner = User::find($challenge->created_by);
    $publishing = app(ChallengePublishing::class);
    $publishing->save($owner, 'module-test-session', challengeData(['record_version' => 3, 'title' => 'New quest', 'test_cases' => [['input' => '99', 'expected_output' => '100', 'hidden' => true]]]), $challenge);
    expect($challenge->fresh()->published_revision_id)->toBe($oldRevision);
    $publishing->submit($owner, 'module-test-session', $challenge->fresh(), 4);
    $publishing->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 5, 'Rejected', 'Review this test');
    expect($challenge->fresh()->published_revision_id)->toBe($oldRevision);
    expect(CodingChallengeRevision::find($oldRevision)->testCases->first()->input)->toBe("2 3\n");
    $this->get(route('challenges.catalog'))->assertSee('Picnic sums')->assertDontSee('New quest');
    moduleSignIn($this, User::factory()->create());
    $this->get(route('challenges.show', $challenge))->assertOk()->assertSee('Picnic sums')->assertDontSee('New quest');
});

test('C05 approved replacements switch the pointer and preserve historical immutable tests', function () {
    $challenge = challengeFixture(true);
    $old = $challenge->published_revision_id;
    $owner = User::find($challenge->created_by);
    $publishing = app(ChallengePublishing::class);
    $publishing->save($owner, 'module-test-session', challengeData(['record_version' => 3, 'title' => 'Replacement quest', 'language' => 'java']), $challenge);
    $publishing->submit($owner, 'module-test-session', $challenge->fresh(), 4);
    $publishing->review(moduleAccount(Role::Administrator), 'module-test-session', $challenge->fresh(), 5, 'Approved', null);
    expect($challenge->fresh()->published_revision_id)->not->toBe($old);
    expect(CodingChallengeRevision::find($old)->testCases)->toHaveCount(2);
    $this->assertDatabaseCount('coding_challenge_revisions', 2);
    $this->assertDatabaseCount('challenge_test_cases', 4);
});

test('C05 pending and stale edits reject changes without touching revision history', function () {
    $challenge = challengeFixture();
    moduleSignIn($this, User::find($challenge->created_by));
    $this->putJson(route('challenges.update', $challenge), challengeData(['record_version' => 99]))->assertUnprocessable();
    $this->post(route('challenges.submit', $challenge), ['record_version' => 1])->assertRedirect();
    $this->putJson(route('challenges.update', $challenge), challengeData(['record_version' => 2]))->assertUnprocessable();
    $this->postJson(route('challenges.submit', $challenge), ['record_version' => 2])->assertUnprocessable();
    $this->assertDatabaseCount('coding_challenge_revisions', 1);
});

test('C02 reviews cannot be performed by the author or a role without moderation privileges', function (Role $role) {
    $challenge = challengeFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('challenge-reviews.index'))->assertForbidden();
    $this->get(route('challenge-reviews.show', $challenge))->assertForbidden();
    $this->postJson(route('challenge-reviews.review', $challenge), ['record_version' => 1, 'decision' => 'Approved', 'confirmed' => true])->assertForbidden();
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('C02 author role elevation cannot allow approving their own challenge', function () {
    $challenge = challengeFixture();
    $owner = User::find($challenge->created_by);
    $owner->update(['account_role' => Role::Moderator]);
    moduleSignIn($this, $owner);
    $this->get(route('challenge-reviews.show', $challenge))->assertForbidden();
    $this->postJson(route('challenge-reviews.review', $challenge), ['record_version' => 1, 'decision' => 'Approved', 'confirmed' => true])->assertForbidden();
});

test('C01 C05 cross creator edit submit and archive attempts cannot access test cases', function () {
    $challenge = challengeFixture(true);
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Contributor]));
    $this->get(route('challenges.edit', $challenge))->assertForbidden();
    $this->putJson(route('challenges.update', $challenge), challengeData(['record_version' => 3]))->assertForbidden();
    $this->postJson(route('challenges.submit', $challenge), ['record_version' => 3])->assertForbidden();
    $this->get(route('challenges.archive-confirmation', $challenge))->assertForbidden();
    $this->postJson(route('challenges.archive', $challenge), ['record_version' => 3, 'confirmed' => true])->assertForbidden();
});

test('B06 published learner preview contains samples and escaped text but no hidden tests or execution endpoint', function () {
    $challenge = challengeFixture(true, overrides: ['description' => '<script>problem</script>',
        'test_cases' => [['input' => '<img src=x>', 'expected_output' => "sample-result\n", 'hidden' => false], ['input' => 'private-input-unique', 'expected_output' => 'private-output-unique', 'hidden' => true]]]);
    $this->get(route('challenges.show', $challenge))->assertRedirect(route('login'));
    $this->get(route('challenges.catalog', ['q' => '%']))->assertDontSee('Picnic sums');
    $this->getJson(route('challenges.catalog', ['q' => str_repeat('x', 81)]))->assertUnprocessable();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('challenges.show', $challenge))->assertOk()->assertSee('sample-result')->assertSee('&lt;img src=x&gt;', false)
        ->assertSee('&lt;script&gt;problem&lt;/script&gt;', false)->assertDontSee('<script>problem', false)
        ->assertDontSee('private-input-unique')->assertDontSee('private-output-unique')->assertDontSee('Submit code')->assertHeader('Cache-Control', 'no-store, private')
        ->assertViewHas('samples', fn ($cases) => $cases->count() === 1);
    $this->post('/challenges/'.$challenge->id.'/submit', ['code' => 'print(5)'])->assertNotFound();
});

test('C06 owning creators confirm archive preserving revisions tests and audit while stopping browsing and edits', function () {
    $challenge = challengeFixture(true);
    moduleSignIn($this, User::find($challenge->created_by));
    $this->get(route('challenges.archive-confirmation', $challenge))->assertOk()->assertSee('Confirm archive');
    $this->postJson(route('challenges.archive', $challenge), ['record_version' => 3])->assertUnprocessable();
    $this->postJson(route('challenges.archive', $challenge), ['record_version' => 2, 'confirmed' => true])->assertUnprocessable();
    $this->post(route('challenges.archive', $challenge), ['record_version' => 3, 'confirmed' => true])->assertRedirect();
    expect($challenge->fresh()->status)->toBe('Archived')->and($challenge->fresh()->record_version)->toBe(4);
    $this->assertDatabaseCount('coding_challenge_revisions', 1);
    $this->assertDatabaseCount('challenge_test_cases', 2);
    $this->assertDatabaseHas('audit_events', ['event' => 'challenge.archived']);
    $this->get(route('challenges.edit', $challenge))->assertOk()->assertSee('challenge is archived');
    $this->get(route('challenges.show', $challenge))->assertNotFound();
    $this->get(route('challenges.catalog'))->assertDontSee('Picnic sums');
    $this->putJson(route('challenges.update', $challenge), challengeData(['record_version' => 4]))->assertForbidden();
    $this->postJson(route('challenges.archive', $challenge), ['record_version' => 4, 'confirmed' => true])->assertForbidden();
});

test('B06 challenge filters use predefined language and difficulty values and only published revisions', function () {
    challengeFixture(true);
    challengeFixture(true, overrides: ['title' => 'Java quest', 'language' => 'java', 'difficulty' => 'Hard']);
    $this->get(route('challenges.catalog', ['language' => 'java', 'difficulty' => 'Hard']))->assertSee('Java quest')->assertDontSee('Picnic sums');
    $this->getJson(route('challenges.catalog', ['language' => 'javascript']))->assertUnprocessable();
    $this->getJson(route('challenges.catalog', ['difficulty' => 'Legendary']))->assertUnprocessable();
});

test('C02 publication rechecks creator eligibility and rolls back notification audit and pointer on failure', function () {
    $challenge = challengeFixture();
    $owner = User::find($challenge->created_by);
    $publishing = app(ChallengePublishing::class);
    $publishing->submit($owner, 'module-test-session', $challenge, 1);
    $reviewer = moduleAccount(Role::Moderator);
    User::whereKey($owner->id)->update(['account_status' => AccountStatus::Suspended]);
    expect(fn () => $publishing->review($reviewer, 'module-test-session', $challenge->fresh(), 2, 'Approved', null))->toThrow(AuthorizationException::class);
    User::whereKey($owner->id)->update(['account_status' => AccountStatus::Active]);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => $publishing->review($reviewer, 'module-test-session', $challenge->fresh(), 2, 'Approved', null))->toThrow(RuntimeException::class);
    expect($challenge->fresh()->status)->toBe('Draft')->and($challenge->fresh()->latestRevision->review_status)->toBe('Pending');
    $this->assertDatabaseCount('notifications', 0);
});

test('C05 writers reject revoked sessions and freshly changed author roles', function () {
    $challenge = challengeFixture();
    $owner = User::find($challenge->created_by);
    User::whereKey($owner->id)->update(['active_session_hash' => hash('sha256', 'replacement')]);
    expect(fn () => app(ChallengePublishing::class)->save($owner, 'module-test-session', challengeData(), $challenge))->toThrow(AuthorizationException::class);
    User::whereKey($owner->id)->update(['active_session_hash' => hash('sha256', 'module-test-session'), 'account_role' => Role::Learner]);
    expect(fn () => app(ChallengePublishing::class)->save($owner, 'module-test-session', challengeData(), $challenge))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('coding_challenge_revisions', 1);
});

test('challenge database failures roll back and report sanitized context without hidden test bindings', function () {
    $actor = moduleAccount(Role::Contributor);
    Log::shouldReceive('error')->once()->with('Challenge storage write failed.', ['sqlstate' => '23514']);
    $failure = null;
    try {
        app(ChallengePublishing::class)->save($actor, 'module-test-session', challengeData(['test_cases' => [
            ['input' => str_repeat('private-test-payload', 5000), 'expected_output' => 'secret-result', 'hidden' => true],
        ]]));
    } catch (RuntimeException $exception) {
        $failure = $exception;
    }
    expect($failure)->toBeInstanceOf(RuntimeException::class);
    expect($failure->getMessage())->toBe('Challenge storage is temporarily unavailable.')->not->toContain('private-test-payload');
    expect($failure->getPrevious())->toBeNull();
    $this->assertDatabaseCount('coding_challenges', 0);
    $this->assertDatabaseCount('coding_challenge_revisions', 0);
    $this->assertDatabaseCount('challenge_test_cases', 0);
});

test('challenge PostgreSQL constraints prevent mismatched publication pointers unsupported languages and invalid cases', function () {
    $challenge = challengeFixture(true);
    $other = challengeFixture();
    expect(fn () => DB::transaction(fn () => $challenge->update(['published_revision_id' => $other->latestRevision->id])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => CodingChallengeRevision::whereKey($challenge->published_revision_id)->update(['language' => 'php'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => ChallengeTestCase::create(['revision_id' => $challenge->published_revision_id, 'position' => 21, 'input' => '', 'expected_output' => '', 'hidden' => true])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => ChallengeTestCase::create(['revision_id' => $challenge->published_revision_id, 'position' => 1, 'input' => '', 'expected_output' => '', 'hidden' => true])))->toThrow(QueryException::class);
});

test('C01 C02 C06 mutations require CSRF and canonical challenge identifiers', function () {
    $challenge = challengeFixture();
    moduleSignIn($this, User::find($challenge->created_by));
    $this->app['env'] = 'local';
    $this->post(route('challenges.store'), challengeData())->assertStatus(419);
    $this->post(route('challenges.submit', $challenge), ['record_version' => 1])->assertStatus(419);
    $this->post(route('challenges.archive', $challenge), ['record_version' => 1, 'confirmed' => true])->assertStatus(419);
    $this->get('/create/challenges/invalid-id')->assertNotFound();
    $this->get('/challenges/invalid-id')->assertNotFound();
});
