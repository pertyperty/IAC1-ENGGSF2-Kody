<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('verification form validation never flashes a secret token into the session', function () {
    $secret = str_repeat('a', 65);
    $this->from(route('verification.notice'))->post(route('verification.verify'), ['verification_token' => $secret])
        ->assertRedirect(route('verification.notice'))->assertSessionHasErrors('verification_token');
    expect(session()->getOldInput('verification_token'))->toBeNull();
});

test('verification secrets cannot be submitted as GET URLs', function () {
    $this->get('/email/verify/'.str_repeat('a', 64))->assertNotFound();
    $this->get(route('verification.notice', ['verification_token' => str_repeat('a', 64)]))->assertOk();
    $this->assertDatabaseCount('users', 0);
});

test('verification activation requires CSRF protection outside the test bypass', function () {
    $this->app['env'] = 'production';
    $this->post(route('verification.verify'), ['verification_token' => str_repeat('a', 64)])
        ->assertStatus(419);
    $this->withSession(['_token' => 'test-csrf-token'])->post(route('verification.verify'), [
        '_token' => 'test-csrf-token', 'verification_token' => str_repeat('a', 64),
    ])->assertStatus(422);
});

test('verification landing page prevents caching and referrer disclosure', function () {
    $this->get(route('verification.notice'))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});
