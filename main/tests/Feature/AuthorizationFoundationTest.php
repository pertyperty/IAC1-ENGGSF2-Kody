<?php

use App\Models\User;

test('unimplemented privileges are denied by Laravel authorization', function () {
    $user = new User;

    expect($user->can('unimplemented-privilege'))->toBeFalse();
});

test('client supplied role and permissions cannot be mass assigned', function () {
    $user = new User;
    $user->fill(['name' => 'Learner', 'role' => 'Administrator', 'permissions' => ['*'], 'account_role' => 'Admin', 'account_status' => 'Active', 'email_verified_at' => now(), 'active_session_hash' => str_repeat('a', 64), 'failed_login_attempts' => 0, 'login_locked_until' => null]);

    expect($user->getAttributes())->toBe(['name' => 'Learner']);
});
