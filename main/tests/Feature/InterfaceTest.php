<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shared account and learning pages provide a keyboard bypass and accessible theme control', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee('href="#main-content"', false)
        ->assertSee('id="main-content"', false)->assertSee('aria-label="Dark mode"', false)
        ->assertSee('data-theme-toggle', false)->assertSee('kody-theme');
})->with(['home', 'login', 'register', 'recovery.request', 'help.index']);

test('D01 unsaved quiz preview is separate from authoring and never records learner completion', function () {
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $response = $this->get(route('studio.create', ['example' => 'choice-quiz']))->assertOk()
        ->assertSee('Preview my quiz')->assertDontSee('data-completion-url', false)
        ->assertSee('Preview answers stay here and do not save progress.');
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    expect($xpath->query('//form//section[@data-quiz-preview]')->length)->toBe(0);
    $this->assertDatabaseCount('learning_modules', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
});

test('G01 staff layouts remain protected while participant pages share the theme', function () {
    $this->get(route('account-governance.index'))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('account-governance.index'))->assertForbidden();
    $this->get(route('account.show'))->assertOk()->assertSee('data-theme-toggle', false);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->get(route('account-governance.index'))->assertOk()->assertSee('account-shell-wide')
        ->assertSee('data-theme-toggle', false);
});
