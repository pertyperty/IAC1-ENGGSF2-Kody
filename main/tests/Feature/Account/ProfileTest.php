<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('A05 every role can view only its own profile with masked email', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role, 'email' => 'private@example.test', 'username' => 'myprofile', 'first_name' => 'Own', 'last_name' => 'Account']);
    $other = User::factory()->create(['username' => 'otherprofile']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('account.show', ['user_id' => $other->id]))->assertOk()->assertSee('myprofile')->assertSee('p***@example.test')
        ->assertDontSee($user->email)->assertDontSee($other->username)->assertDontSee($user->password)
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/account/'.$other->id)->assertNotFound();
})->with(Role::cases());

test('A05 partial legacy profiles remain readable and escape user content', function () {
    $user = User::factory()->create(['username' => null, 'first_name' => '<script>alert(1)</script>', 'last_name' => null]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('account.show'))->assertOk()->assertSee('Not yet provided')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

test('A05 profile requires a current active session', function () {
    $this->get(route('account.show'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('account.show'))->assertRedirect(route('login'));
});
