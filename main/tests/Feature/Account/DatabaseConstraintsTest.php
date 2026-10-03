<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('account role and lifecycle values are constrained by PostgreSQL', function (string $column, string $value) {
    $user = User::factory()->create();
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update([$column => $value])))->toThrow(QueryException::class);
})->with([
    ['account_role', 'Superuser'],
    ['account_status', 'Invalid'],
    ['username', 'short'],
]);

test('PostgreSQL rejects duplicate email addresses regardless of casing', function () {
    User::factory()->create(['email' => 'learner@example.test']);
    expect(fn () => DB::transaction(fn () => User::factory()->create(['email' => 'Learner@example.test'])))->toThrow(QueryException::class);
});

test('PostgreSQL rejects active accounts without email verification', function () {
    $user = User::factory()->create();
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update(['email_verified_at' => null])))->toThrow(QueryException::class);
});
