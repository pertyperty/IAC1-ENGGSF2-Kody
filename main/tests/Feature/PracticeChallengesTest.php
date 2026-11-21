<?php

use App\Services\Games\PracticeChallenges;

test('practice difficulty is validated and does not deliver reference programs', function () {
    $this->get('/playground?game=number-machine&level=warmup')->assertOk()
        ->assertSee('Charge the battery')->assertSee('Warm-up')->assertDontSee('&quot;reference&quot;', false);
    $this->get('/playground?game=loops&level=stretch')->assertOk()->assertSee('Repeat around the bend');
    $this->getJson('/playground?game=number-machine&level=impossible')->assertUnprocessable();
    $this->getJson('/playground?game=unknown&level=challenge')->assertUnprocessable();
    $this->get('/playground?game=quiz-lab&level=warmup')->assertOk()->assertSee('Quiz Lab');
});

test('practice tiers retain concept checks while keeping saved assessment instances untouched', function () {
    $challenge = app(PracticeChallenges::class)->instance('logic-switchboard', 'challenge');
    $stretch = app(PracticeChallenges::class)->instance('logic-switchboard', 'stretch');
    expect($challenge['scenario']['gate'])->toBe('and')
        ->and($stretch['scenario']['gate'])->toBe('or')
        ->and($stretch['assessment']['check']['question'])->toContain('OR')
        ->and($challenge['assessment']['id'])->toBe('logic-decision')
        ->and($stretch['assessment']['id'])->toBe('practice-logic-switchboard-stretch');
});
