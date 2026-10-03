<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(RefreshDatabase::class);

function databaseBrowserRequest(TestCase $test, string $method, string $route, array $data = [], ?string $sessionId = null): TestResponse
{
    config(['session.driver' => 'database']);
    app('auth')->forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    $test->withCredentials()->withCookie(config('session.cookie'), $sessionId ?? Str::random(40));

    return $test->{$method}($route, $data);
}

test('A03 independent database sessions require consent and reject the previous browser after replacement', function () {
    $user = User::factory()->create();
    $credentials = ['email' => $user->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route('dashboard'));
    $firstBrowser = session()->getId();
    $this->assertDatabaseHas('sessions', ['id' => $firstBrowser, 'user_id' => $user->id]);
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $firstBrowser)->assertOk();

    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route('login.confirmation'));
    $secondBrowser = session()->getId();
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', $firstBrowser));
    databaseBrowserRequest($this, 'post', route('login.confirm'), ['choice' => 'continue'], $secondBrowser)->assertRedirect(route('dashboard'));
    $replacementBrowser = session()->getId();
    expect($replacementBrowser)->not->toBe($secondBrowser);
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $firstBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $replacementBrowser)->assertOk()->assertSee($user->email);
});

test('A03 confirmation proof is bound to its guest browser and cancelling preserves the original browser', function () {
    $user = User::factory()->create();
    $credentials = ['email' => $user->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $firstBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $pendingBrowser = session()->getId();

    databaseBrowserRequest($this, 'postJson', route('login.confirm'), ['choice' => 'continue'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Invalid email or password.');
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', $firstBrowser));
    databaseBrowserRequest($this, 'post', route('login.confirm'), ['choice' => 'cancel'], $pendingBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $firstBrowser)->assertOk();
});

test('A03 logout from a replaced database session cannot terminate its successor', function () {
    $user = User::factory()->create();
    $credentials = ['email' => $user->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $firstBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $pendingBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('login.confirm'), ['choice' => 'continue'], $pendingBrowser);
    $replacementBrowser = session()->getId();

    databaseBrowserRequest($this, 'post', route('logout'), sessionId: $firstBrowser)->assertRedirect(route('login'));
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', $replacementBrowser));
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $replacementBrowser)->assertOk();
    databaseBrowserRequest($this, 'post', route('logout'), sessionId: $replacementBrowser)->assertRedirect(route('login'));
    expect($user->fresh()->active_session_hash)->toBeNull();
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $replacementBrowser)->assertRedirect(route('login'));
});
