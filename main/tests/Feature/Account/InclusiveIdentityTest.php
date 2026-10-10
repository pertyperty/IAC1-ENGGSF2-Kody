<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('A01 accepts multiword Unicode names and ordinary family-name punctuation', function (string $first, string $last) {
    $this->postJson(route('register.store'), registrationData(['first_name' => $first, 'last_name' => $last]))->assertCreated();
    expect(User::sole()->first_name)->toBe($first)->and(User::sole()->last_name)->toBe($last);
})->with([['Maria Clara', 'De La Cruz'], ['José', "O'Connor"], ['Anne-Marie', 'Dela Peña']]);

test('A03 username login uses the same account lockout as email and lands learners on the tower', function () {
    $user = User::factory()->create(['username' => 'My_Player']);
    $this->postJson(route('login.store'), ['email' => $user->username, 'password' => 'wrong'])->assertUnprocessable();
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    $this->postJson(route('login.store'), ['email' => $user->username, 'password' => 'wrong'])->assertUnprocessable();
    expect($user->fresh()->failed_login_attempts)->toBe(3);
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertJsonPath('errors.email.0', 'This account is temporarily locked. Try again after the cooldown.');
    $this->travel(16)->minutes();
    $this->post(route('login.store'), ['email' => ' My_Player ', 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('A03 distinct case-sensitive usernames never resolve to an arbitrary account', function () {
    $first = User::factory()->create(['username' => 'PlayerOne']);
    User::factory()->create(['username' => 'playerone']);
    $this->post(route('login.store'), ['email' => 'PlayerOne', 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($first);
});

test('A03 email takes precedence over an overlapping legacy username', function () {
    $emailOwner = User::factory()->create(['email' => 'overlap@example.test']);
    User::factory()->create(['username' => $emailOwner->email]);
    $this->post(route('login.store'), ['email' => $emailOwner->email, 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($emailOwner);
});

test('A06 profile editing accepts a multiword last name while preserving role and status', function () {
    $user = User::factory()->create(['account_role' => Role::Instructor]);
    moduleSignIn($this, $user);
    $this->patch(route('account.update'), $user->only(['username', 'email', 'first_name']) + ['last_name' => 'De La Cruz', 'profile_version' => 1])->assertRedirect();
    expect($user->fresh()->last_name)->toBe('De La Cruz')->and($user->fresh()->account_role)->toBe(Role::Instructor);
});
