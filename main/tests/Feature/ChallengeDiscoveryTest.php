<?php

use App\Enums\Role;
use App\Services\Challenges\ChallengePublishing;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('B06 approved challenge topics and concepts combine with language difficulty and literal search', function () {
    $match = challengeFixture(true, overrides: ['category' => 'numbers', 'tags' => ['variables', 'conditions']]);
    challengeFixture(true, overrides: ['title' => 'Other topic', 'category' => 'strings', 'tags' => ['conditions']]);
    challengeFixture(true, overrides: ['title' => 'Other concept', 'category' => 'numbers', 'tags' => ['loops']]);
    challengeFixture(false, overrides: ['title' => 'Secret draft', 'category' => 'numbers', 'tags' => ['conditions']]);
    $response = $this->get(route('challenges.catalog', ['q' => 'Picnic', 'category' => 'numbers', 'tag' => 'conditions', 'language' => 'python', 'difficulty' => 'Easy']));
    $response->assertOk()->assertSee($match->publishedRevision->title)->assertSee('Numbers and arithmetic')->assertDontSee('Other topic')->assertDontSee('Other concept')->assertDontSee('Secret draft');
    expect($response['challenges']->total())->toBe(1);
    expect($response['challenges']->first()->getAttributes())->not->toHaveKeys(['description', 'rules', 'input_format']);
    $this->getJson(route('challenges.catalog', ['category' => 'invented']))->assertUnprocessable();
    $this->getJson(route('challenges.catalog', ['tag' => ['loops']]))->assertUnprocessable();
    $this->get(route('challenges.catalog', ['q' => '%']))->assertOk()->assertDontSee('Picnic sums');
});

test('B06 pending replacement metadata cannot alter published discovery and staff withdrawal hides it', function () {
    $challenge = challengeFixture(true, overrides: ['category' => 'numbers', 'tags' => ['variables']]);
    $author = $challenge->creator;
    $service = app(ChallengePublishing::class);
    $service->save($author, 'module-test-session', challengeData(['record_version' => 3, 'category' => 'strings', 'tags' => ['strings']]), $challenge);
    $service->submit($author, 'module-test-session', $challenge->fresh(), 4);
    $this->get(route('challenges.catalog', ['category' => 'numbers', 'tag' => 'variables']))->assertSee('Picnic sums');
    $this->get(route('challenges.catalog', ['category' => 'strings', 'tag' => 'strings']))->assertDontSee('Picnic sums');
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 5, 'Approved', null);
    $this->get(route('challenges.catalog', ['category' => 'strings', 'tag' => 'strings']))->assertSee('Picnic sums');
    $challenge->fresh()->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->get(route('challenges.catalog', ['category' => 'strings']))->assertDontSee('Picnic sums');
});

test('C01 challenge metadata rejects unbounded unknown duplicate and malformed tags at HTTP and service boundaries', function (array $metadata) {
    $author = moduleAccount(Role::Contributor);
    expect(fn () => app(ChallengePublishing::class)->save($author, 'module-test-session', challengeData($metadata)))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('coding_challenges', 0);
    moduleSignIn($this, $author);
    $this->postJson(route('challenges.store'), challengeData($metadata))->assertUnprocessable();
    $this->assertDatabaseCount('coding_challenges', 0);
})->with([
    [['category' => 'unknown']], [['tags' => ['unknown']]], [['tags' => ['loops', 'loops']]],
    [['tags' => ['variables', 'conditions', 'loops', 'functions', 'arrays', 'strings']]],
    [['tags' => ['named' => 'loops']]], [['tags' => 'loops']], [['tags' => null]], [['category' => null]],
]);

test('C01 PostgreSQL enforces the stored discovery vocabulary', function (string $field, mixed $value) {
    $challenge = challengeFixture();
    expect(fn () => DB::transaction(fn () => DB::table('coding_challenge_revisions')->where('id', $challenge->latestRevision->id)->update([$field => $value])))->toThrow(QueryException::class);
})->with([['category', 'unknown'], ['tags', '["unknown"]'], ['tags', '{}'], ['tags', '["loops","loops","loops","loops","loops","loops"]']]);

test('B06 empty metadata defaults preserve old authoring and reviewed learner problems show concepts without hidden cases', function () {
    $challenge = challengeFixture(true);
    expect($challenge->publishedRevision->category)->toBe('foundations')->and($challenge->publishedRevision->tags)->toBe([]);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('challenges.show', $challenge))->assertOk()->assertSee('Programming basics')->assertDontSee('4 8');
});

test('C05 rollback cannot discard reviewed challenge discovery metadata', function () {
    $challenge = challengeFixture(true, overrides: ['category' => 'numbers', 'tags' => ['variables']]);
    $migration = require database_path('migrations/2026_10_05_000038_add_challenge_discovery_metadata.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Retained challenge discovery metadata');
    expect($challenge->fresh()->publishedRevision->tags)->toBe(['variables']);
    $this->get(route('challenges.catalog', ['category' => 'numbers', 'tag' => 'variables']))->assertSee('Picnic sums');
});
