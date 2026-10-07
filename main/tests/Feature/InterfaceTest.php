<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff workspace respects existing role policies and guest/participant navigation', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    $response = $this->get(route('dashboard'))->assertOk();
    if (in_array($role, [Role::Moderator, Role::Administrator], true)) {
        $response->assertSee('YOUR STAFF WORKSPACE')->assertSee('Community accounts')->assertSee('Module reviews');
        $this->get(route('account.show'))->assertOk()->assertSee('account-shell-wide')->assertSee('Account navigation');
        $this->get(route('account-governance.index'))->assertOk()->assertSee('account-result', false)->assertSee('Apply filters');
        if ($role === Role::Administrator) {
            $response->assertSee('Finance workspace')->assertSee('Game presets')->assertDontSee('Prepare future weeks');
        } else {
            $response->assertSee('Prepare future weeks')->assertDontSee('Finance workspace')->assertDontSee('Game presets');
        }
    } else {
        $response->assertDontSee('YOUR STAFF WORKSPACE')->assertDontSee('Finance workspace');
        $this->get(route('account-governance.index'))->assertForbidden();
    }
})->with(Role::cases());

test('login and registration offer progressive password visibility without echoing old secrets', function () {
    foreach (['login', 'register'] as $route) {
        $response = $this->withSession(['_old_input' => ['password' => 'NeverEchoSecret12!', 'password_confirmation' => 'NeverEchoSecret12!']])
            ->get(route($route))->assertOk()->assertSee('data-password-toggle', false)
            ->assertSee('type="password"', false)->assertDontSee('NeverEchoSecret12!');
    }
});

test('validated quiz copy reflects adopted XP while unsaved previews promise no persisted progress', function () {
    signInForLearning($this);
    $this->get(route('learning.show', 'sequences'))->assertOk()->assertSee('eligible first-completion XP')
        ->assertSee('This quiz awards no KodeBits.')->assertDontSee('No grades, XP or KodeBits.');
});

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
