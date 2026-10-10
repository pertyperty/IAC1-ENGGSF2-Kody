<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('game-first amendment offers a public playable trial and separate learning tab', function () {
    $this->get(route('welcome'))->assertOk()->assertSee('data-coding-game', false)->assertSee('Run my code')->assertSee('Logic Garden')
        ->assertSee(route('learning.catalog'))->assertSee('Sign in to build a daily streak');
    $this->assertDatabaseCount('users', 0);
});

test('Learning catalog exposes all practice modules and searches their concepts', function () {
    $this->get(route('learning.catalog'))->assertOk()->assertSee('First steps')->assertSee('The loop trail')->assertSee('Crystal collector');
    $this->get(route('learning.catalog', ['q' => 'LOOPS']))->assertOk()->assertSee('The loop trail')->assertDontSee('Crystal collector')->assertDontSee('First steps');
    $this->get(route('learning.catalog', ['q' => 'not a module']))->assertOk()->assertSee('No adventures found')->assertSee('See all modules');
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

test('authenticated module games use the correct template and explain first-clear XP without KodeBit rewards', function (string $slug) {
    $user = User::factory()->create();
    foreach (array_slice(['sequences', 'loops', 'conditions'], 0, array_search($slug, ['sequences', 'loops', 'conditions'])) as $previous) {
        DB::table('learning_level_completions')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'level' => $previous, 'template_version' => 1, 'completed_at' => now()]);
    }
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('learning.show', $slug))->assertOk()->assertSee(config('learning.instances.'.$slug.'.title'))
        ->assertSee('data-coding-game', false)->assertSee('data-practice-quiz', false)->assertSee('20 XP')->assertSee('awards no KodeBits')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('learning.show', 'unknown'))->assertNotFound();
})->with(['sequences', 'loops', 'conditions']);

test('suspended accounts cannot enter a module through an existing session', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('learning.show', 'loops'))->assertRedirect(route('login'));
});
