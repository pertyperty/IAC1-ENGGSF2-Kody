<?php

use App\Enums\Role;
use App\Services\Challenges\ChallengePublishing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('B06 E02 challenge problems display metadata samples and forms from the correct approved revision', function (bool $weekly) {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
    Http::preventStrayRequests();
    readyJudge();
    $challenge = challengeFixture(true, overrides: [
        'category' => 'numbers', 'tags' => ['variables'],
        'test_cases' => [
            ['input' => 'original-public-input', 'expected_output' => 'original-public-output', 'hidden' => false],
            ['input' => 'original-private-input', 'expected_output' => 'original-private-output', 'hidden' => true],
        ],
    ]);
    $originalRevisionId = $challenge->published_revision_id;
    $event = $weekly ? weeklyFixture($challenge) : null;
    $publishing = app(ChallengePublishing::class);
    $publishing->save($challenge->creator, 'module-test-session', challengeData([
        'record_version' => 3, 'title' => 'Replacement problem', 'category' => 'strings', 'tags' => ['functions'],
        'test_cases' => [
            ['input' => 'replacement-public-input', 'expected_output' => 'replacement-public-output', 'hidden' => false],
            ['input' => 'replacement-private-input', 'expected_output' => 'replacement-private-output', 'hidden' => true],
        ],
    ]), $challenge);
    $publishing->submit($challenge->creator, 'module-test-session', $challenge->fresh(), 4);
    $publishing->review(moduleAccount(Role::Moderator), 'module-test-session', $challenge->fresh(), 5, 'Approved', null);
    $currentRevisionId = $challenge->fresh()->published_revision_id;
    expect($currentRevisionId)->not->toBe($originalRevisionId);

    // Reproduce a publication completing after route binding but before access admission.
    Route::bind('challenge', fn () => $challenge);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $response = $this->get($weekly ? route('weekly-events.show', $event) : route('challenges.show', $challenge));
    $response->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee($weekly ? 'Picnic sums' : 'Replacement problem')
        ->assertDontSee($weekly ? 'Replacement problem' : 'Picnic sums')
        ->assertSee($weekly ? 'Numbers and arithmetic' : 'Text and strings')
        ->assertSee($weekly ? 'Variables' : 'Functions')
        ->assertSee($weekly ? 'original-public-input' : 'replacement-public-input')
        ->assertSee($weekly ? 'original-public-output' : 'replacement-public-output')
        ->assertDontSee($weekly ? 'replacement-public-input' : 'original-public-input')
        ->assertDontSee($weekly ? 'replacement-public-output' : 'original-public-output')
        ->assertDontSee('original-private-input')->assertDontSee('original-private-output')
        ->assertDontSee('replacement-private-input')->assertDontSee('replacement-private-output')
        ->assertSee('name="revision_id" value="'.($weekly ? $originalRevisionId : $currentRevisionId).'"', false);

    expect($response['revision']->getAttributes())->toHaveKeys(['category', 'tags'])
        ->not->toHaveKeys(['review_notes', 'reviewed_by', 'price_kb', 'minimum_xp']);
    expect($response['samples'])->toHaveCount(1);
    expect(array_keys($response['samples']->sole()->getAttributes()))->toBe(['position', 'input', 'expected_output']);
    $this->assertDatabaseCount('challenge_submissions', 0);
    Http::assertNothingSent();
})->with(['standard current publication' => false, 'weekly pinned publication' => true]);
