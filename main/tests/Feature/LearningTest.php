<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('game-first amendment offers a public playable trial and separate learning tab', function () {
    $this->get(route('home'))->assertOk()->assertSee('data-coding-game', false)->assertSee('Run my code')->assertSee('Logic Garden')
        ->assertSee(route('learning.catalog'))->assertSee('Daily streaks and a level ladder are coming');
    $this->assertDatabaseCount('users', 0);
});

test('Learning catalog exposes all practice modules and searches their concepts', function () {
    $this->get(route('learning.catalog'))->assertOk()->assertSee('First steps')->assertSee('The loop trail')->assertSee('Crystal collector');
    $this->get(route('learning.catalog', ['q' => 'LOOPS']))->assertOk()->assertSee('The loop trail')->assertDontSee('Crystal collector')->assertDontSee('First steps');
    $this->get(route('learning.catalog', ['q' => 'not a module']))->assertOk()->assertSee('No adventures match that search');
});

test('Learning search escapes input and bounds query size', function () {
    $this->get(route('learning.catalog', ['q' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    $this->getJson(route('learning.catalog', ['q' => str_repeat('x', 81)]))->assertJsonValidationErrors('q');
    $this->getJson(route('learning.catalog', ['q' => ['loops']]))->assertJsonValidationErrors('q');
});

test('guest module access redirects to sign-in with registration available', function (string $slug) {
    $this->get(route('learning.show', $slug))->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk()->assertSee(route('register'));
})->with(['sequences', 'loops', 'conditions']);

test('authenticated module games use the correct template instance and remain practice only', function (string $slug) {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('learning.show', $slug))->assertOk()->assertSee(config('learning.instances.'.$slug.'.title'))
        ->assertSee('data-coding-game', false)->assertSee('data-practice-quiz', false)->assertSee('doesn’t award KodeBits')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('learning.show', 'unknown'))->assertNotFound();
})->with(['sequences', 'loops', 'conditions']);

test('suspended accounts cannot enter a module through an existing session', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('learning.show', 'loops'))->assertRedirect(route('login'));
});
