<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function conflictingLogin(): User
{
    return User::factory()->create([
        'active_session_hash' => hash('sha256', 'previous-session'),
        'active_session_expires_at' => now()->addMinutes(30),
        'failed_login_attempts' => 2,
    ]);
}

test('A03 conflict prompts only after correct credentials and continue replaces the previous session', function () {
    $user = conflictingLogin();
    $oldHash = $user->active_session_hash;
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertConflict();
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBe($oldHash)->and($user->fresh()->failed_login_attempts)->toBe(2)
        ->and(session('login_confirmation'))->not->toHaveKey('password');
    $this->get(route('login.confirmation'))->assertOk()->assertSee('Continue here?');
    $this->post(route('login.confirm'), ['choice' => 'continue', 'user_id' => 999])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->active_session_hash)->not->toBe($oldHash)
        ->and($user->fresh()->failed_login_attempts)->toBe(0)->and(session('login_confirmation'))->toBeNull();
});

test('A03 cancelling a conflicting login preserves the existing session', function () {
    $user = conflictingLogin();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('login.confirmation'));
    $this->post(route('login.confirm'), ['choice' => 'cancel'])->assertRedirect(route('login'));
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', 'previous-session'))->and(session('login_confirmation'))->toBeNull();
    $this->postJson(route('login.confirm'), ['choice' => 'continue'])->assertUnprocessable();
});

test('A03 absent expired or stale confirmation cannot replace another session', function (string $change) {
    $user = conflictingLogin();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    match ($change) {
        'absent' => session()->forget('login_confirmation'),
        'expired' => $this->travel(5)->minutes(),
        'password' => $user->forceFill(['password' => 'ChangedPass12!'])->save(),
        'session' => $user->forceFill(['active_session_hash' => hash('sha256', 'third-session')])->save(),
        'suspended' => $user->forceFill(['account_status' => AccountStatus::Suspended])->save(),
        'locked' => $user->forceFill(['login_locked_until' => now()->addHour()])->save(),
    };
    $oldHash = $user->fresh()->active_session_hash;
    $this->postJson(route('login.confirm'), ['choice' => 'continue'])->assertUnprocessable();
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBe($oldHash);
})->with(['absent', 'expired', 'password', 'session', 'suspended', 'locked']);

test('A03 an expired previous session does not require replacement confirmation', function () {
    $user = conflictingLogin();
    $user->forceFill(['active_session_expires_at' => now()])->save();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('A03 stale sessions cannot sign out the replacement session', function () {
    $user = conflictingLogin();
    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', 'previous-session'));
    $this->assertGuest();
});

test('A03 confirmation never trusts client identity without credential proof', function () {
    $user = conflictingLogin();
    $this->postJson(route('login.confirm'), ['choice' => 'continue', 'user_id' => $user->id])->assertUnprocessable();
    $this->postJson(route('login.confirm'), ['choice' => 'force'])->assertJsonValidationErrors('choice');
    $this->get(route('login.confirmation'))->assertRedirect(route('login'));
    $this->assertGuest();
});
