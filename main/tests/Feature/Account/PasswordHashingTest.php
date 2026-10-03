<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('A01 Argon2id preserves every byte of valid multibyte passwords', function () {
    config(['hashing.driver' => 'argon2id']);
    Hash::forgetDrivers();
    $password = 'Aa1!'.str_repeat('😀', 28);
    $data = [
        'username' => 'unicodelearner', 'email' => 'unicode@example.test',
        'first_name' => 'Maria', 'last_name' => 'Cruz', 'account_type' => 'learner',
        'password' => $password, 'password_confirmation' => $password,
    ];
    $this->postJson(route('register.store'), $data)->assertCreated();
    $hash = User::sole()->password;
    expect(password_get_info($hash)['algoName'])->toBe('argon2id')
        ->and(Hash::check($password, $hash))->toBeTrue()
        ->and(Hash::check('Aa1!'.str_repeat('😀', 27).'😁', $hash))->toBeFalse();
});

test('A03 verified legacy bcrypt passwords upgrade to the configured Argon2id hasher', function () {
    $user = User::factory()->create();
    config(['hashing.driver' => 'argon2id']);
    Hash::forgetDrivers();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    expect(password_get_info($user->fresh()->password)['algoName'])->toBe('argon2id')
        ->and(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
