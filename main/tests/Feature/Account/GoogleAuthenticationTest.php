<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\AccountSecurity;
use App\Services\Account\GoogleAuthentication;
use App\Services\Account\GoogleOAuth;
use App\Services\Administration\AuditRecorder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function googleProviderResponses(array $claims = [], ?array $responses = null): array
{
    config(['services.google' => ['enabled' => true, 'client_id' => 'test-client', 'client_secret' => 'test-secret', 'redirect' => 'http://localhost/auth/google/callback']]);
    $history = new ArrayObject;
    $handler = HandlerStack::create(new MockHandler($responses ?? [
        new Response(200, [], json_encode(['access_token' => 'test-access-token', 'token_type' => 'Bearer'])),
        new Response(200, [], json_encode($claims + ['sub' => 'google-subject-one', 'email' => 'google@example.test', 'email_verified' => true])),
    ]));
    $handler->push(Middleware::history($history));
    app()->instance(GoogleOAuth::class, new GoogleOAuth(new Client(['handler' => $handler, 'timeout' => 8, 'connect_timeout' => 3, 'allow_redirects' => false])));

    return [$history];
}

function googleIdentity(User $user, string $subject = 'google-subject-one'): string
{
    $hash = hash('sha256', "google\0".$subject);
    DB::table('google_identities')->insert(['user_id' => $user->id, 'subject_hash' => $hash, 'record_version' => 1, 'linked_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    return $hash;
}

function googleLinkData(User $user): array
{
    return ['current_password' => 'password', 'profile_version' => $user->fresh()->profile_version,
        'identity_version' => app(GoogleAuthentication::class)->state($user)['identity_version']];
}

function googleCallback($test, ?string $state = null)
{
    $test->withCookie(config('session.cookie'), session()->getId());

    return $test->get(route('google.callback', ['state' => $state ?? session('state'), 'code' => 'test-code']));
}

test('A03 Google remains unavailable until configuration is valid', function () {
    config(['services.google.enabled' => false]);
    $this->get(route('login'))->assertOk()->assertDontSee('Continue with linked Google');
    $this->post(route('google.start'))->assertRedirect(route('login'));
    expect(DB::table('google_auth_attempts')->count())->toBe(0);
});

test('A03 Google redirects require configured application origin and safe callback', function (string $uri) {
    googleProviderResponses();
    config(['services.google.redirect' => $uri]);
    expect(app(GoogleOAuth::class)->available())->toBeFalse();
})->with(['http://attacker.test/auth/google/callback', 'http://localhost/auth/google/callback?next=evil', 'http://localhost/auth/google/callback#fragment', 'http://user:pass@localhost/auth/google/callback', 'http://localhost/wrong']);

test('A06 every verified Active role can explicitly link a Google identity after password confirmation', function (Role $role) {
    [$history] = googleProviderResponses();
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('account.google'))->assertOk()->assertSee('Current Kody password');
    $this->post(route('google.link'), googleLinkData($user))->assertRedirect();
    $state = session('state');
    googleCallback($this)->assertRedirect(route('login'))->assertHeader('Referrer-Policy', 'no-referrer');
    $this->assertGuest();
    expect(DB::table('google_identities')->where('user_id', $user->id)->value('subject_hash'))->toBe(hash('sha256', "google\0google-subject-one"))
        ->and($user->fresh()->email)->toBe($user->email)->and($user->fresh()->active_session_hash)->toBeNull()
        ->and($user->fresh()->profile_version)->toBe(2)->and(count($history))->toBe(2)
        ->and(session('state'))->toBeNull()->and(session('code_verifier'))->toBeNull();
    $this->assertDatabaseHas('audit_events', ['event' => 'account.google-linked']);
    googleCallback($this, $state)->assertRedirect(route('login'));
    expect(count($history))->toBe(2);
})->with(Role::cases());

test('A03 Google sign-in uses PKCE and server userinfo without storing tokens', function () {
    [$history] = googleProviderResponses();
    $user = User::factory()->create(['failed_login_attempts' => 2]);
    googleIdentity($user);
    $response = $this->post(route('google.start'))->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    expect($query['scope'])->toBe('openid email')->and($query['code_challenge_method'])->toBe('S256')
        ->and($query['state'])->toBe(session('state'))->and($query['code_challenge'])->toBe(rtrim(strtr(base64_encode(hash('sha256', session('code_verifier'), true)), '+/', '-_'), '='));
    googleCallback($this)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->failed_login_attempts)->toBe(0)->and(count($history))->toBe(2);
    $tokenRequest = $history[0]['request'];
    parse_str((string) $tokenRequest->getBody(), $body);
    expect((string) $tokenRequest->getUri())->toBe('https://www.googleapis.com/oauth2/v4/token')
        ->and($body)->toHaveKeys(['code_verifier', 'client_secret', 'redirect_uri'])
        ->and($history[1]['request']->getHeaderLine('Authorization'))->toBe('Bearer test-access-token');
    expect(json_encode(session()->all()))->not->toContain('test-access-token')->not->toContain('test-secret');
});

test('A03 Google email matching never automatically links or registers an account', function () {
    $user = User::factory()->create(['email' => 'google@example.test']);
    googleProviderResponses();
    $this->post(route('google.start'));
    googleCallback($this)->assertRedirect(route('login'));
    $this->assertGuest();
    expect(User::count())->toBe(1)->and(DB::table('google_identities')->count())->toBe(0)->and($user->fresh()->last_login_at)->toBeNull();
});

test('A03 unverified malformed and failed Google responses cannot authenticate', function (array $claims) {
    [$history] = googleProviderResponses($claims);
    googleIdentity(User::factory()->create());
    $this->post(route('google.start'));
    googleCallback($this)->assertRedirect(route('login'))->assertSessionMissing('_old_input');
    $this->assertGuest();
    expect(count($history))->toBe(2)->and(session('code_verifier'))->toBeNull();
})->with([[['email_verified' => false]], [['email_verified' => 'true']], [['sub' => null]], [['sub' => '']], [['email' => 'invalid']]]);

test('A03 invalid expired overwritten and replayed callback proofs fail before provider access', function (string $change) {
    [$history] = googleProviderResponses();
    $this->post(route('google.start'));
    $state = session('state');
    $this->withCookie(config('session.cookie'), session()->getId());
    match ($change) {
        'state' => $state = 'wrong-state',
        'expired' => DB::table('google_auth_attempts')->update(['created_at' => now()->subMinutes(10), 'expires_at' => now()->subMinute()]),
        'replayed' => DB::table('google_auth_attempts')->update(['consumed_at' => now()]),
        'session' => DB::table('google_auth_attempts')->update(['session_hash' => hash('sha256', 'another-session')]),
        'overwritten' => $this->post(route('google.start')),
    };
    googleCallback($this, $state)->assertRedirect(route('login'))->assertHeader('Cache-Control', 'no-store, private');
    expect(count($history))->toBe(0);
})->with(['state', 'expired', 'replayed', 'session', 'overwritten']);

test('A06 linking requires correct password and fresh account versions', function (string $change) {
    [$history] = googleProviderResponses();
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $data = googleLinkData($user);
    $data[$change] = $change === 'current_password' ? 'wrong' : 999;
    $this->postJson(route('google.link'), $data)->assertUnprocessable();
    expect(DB::table('google_auth_attempts')->count())->toBe(0)->and(count($history))->toBe(0);
})->with(['current_password', 'profile_version', 'identity_version']);

test('A06 stale linking callbacks cannot attach identities', function (string $change) {
    googleProviderResponses();
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->post(route('google.link'), googleLinkData($user));
    match ($change) {
        'profile' => $user->forceFill(['profile_version' => 2])->save(),
        'session' => $user->fresh()->forceFill(['active_session_hash' => null, 'active_session_expires_at' => null])->save(),
        'suspended' => $user->forceFill(['account_status' => AccountStatus::Suspended])->save(),
    };
    googleCallback($this)->assertRedirect();
    expect(DB::table('google_identities')->count())->toBe(0);
})->with(['profile', 'session', 'suspended']);

test('A06 Google identity ownership cannot move between accounts', function () {
    googleProviderResponses();
    $owner = User::factory()->create();
    googleIdentity($owner);
    $other = User::factory()->create();
    moduleSignIn($this, $other);
    $this->post(route('google.link'), googleLinkData($other));
    googleCallback($this)->assertRedirect(route('account.google'));
    expect(DB::table('google_identities')->count())->toBe(1)->and(DB::table('google_identities')->value('user_id'))->toBe($owner->id)
        ->and($other->fresh()->profile_version)->toBe(1);
});

test('A06 unlink requires password and preserves a versioned tombstone even when provider is disabled', function () {
    $user = User::factory()->create();
    googleIdentity($user);
    moduleSignIn($this, $user);
    $this->post(route('google.unlink'), googleLinkData($user))->assertRedirect(route('login'));
    $this->assertGuest();
    expect(app(GoogleAuthentication::class)->state($user))->toBe(['linked' => false, 'identity_version' => 2])
        ->and($user->fresh()->profile_version)->toBe(2);
    $this->assertDatabaseHas('audit_events', ['event' => 'account.google-unlinked']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
});

test('A03 Google authentication preserves account status restrictions', function (AccountStatus $status) {
    googleProviderResponses();
    $user = User::factory()->create(['account_status' => $status]);
    googleIdentity($user);
    $this->post(route('google.start'));
    googleCallback($this)->assertRedirect(route('login'));
    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
})->with([AccountStatus::Unverified, AccountStatus::Suspended, AccountStatus::Archived, AccountStatus::Deleted]);

test('A03 Google cannot bypass password lockouts or missing local email verification', function (string $restriction) {
    googleProviderResponses();
    $user = User::factory()->create($restriction === 'locked' ? ['login_locked_until' => now()->addHour()] : ['email_verified_at' => null, 'account_status' => AccountStatus::Unverified]);
    googleIdentity($user);
    $this->post(route('google.start'));
    googleCallback($this)->assertRedirect(route('login'));
    $this->assertGuest();
})->with(['locked', 'unverified']);

test('A03 Google uses existing session conflict confirmation and invalidates unlinked proofs', function (bool $unlink) {
    googleProviderResponses();
    $user = conflictingLogin();
    googleIdentity($user);
    $this->post(route('google.start'));
    googleCallback($this)->assertRedirect(route('login.confirmation'));
    $this->assertGuest();
    if ($unlink) {
        DB::table('google_identities')->update(['subject_hash' => null, 'linked_at' => null, 'record_version' => 2]);
        $this->postJson(route('login.confirm'), ['choice' => 'continue'])->assertUnprocessable();
        $this->assertGuest();
    } else {
        $this->post(route('login.confirm'), ['choice' => 'continue'])->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
        $this->assertAuthenticatedAs($user);
    }
})->with([true, false]);

test('A03 callback cancellation and provider errors expose no secrets and consume the attempt', function () {
    [$history] = googleProviderResponses(responses: [new Response(500, [], 'test-access-token test-secret test-code')]);
    $this->post(route('google.start'));
    $state = session('state');
    googleCallback($this)->assertRedirect(route('login'))->assertSessionMissing('_old_input');
    expect(DB::table('google_auth_attempts')->value('consumed_at'))->not->toBeNull();
    expect(json_encode(session()->all()))->not->toContain('test-access-token')->not->toContain('test-secret')->not->toContain('test-code');
    googleCallback($this, $state);
    expect(count($history))->toBe(1);
});

test('A06 guests cannot manage Google identities', function () {
    $this->get(route('account.google'))->assertRedirect(route('login'));
    $this->post(route('google.link'))->assertRedirect(route('login'));
    $this->post(route('google.unlink'))->assertRedirect(route('login'));
});

test('A03 expired OAuth attempts are pruned while recent records survive', function () {
    googleProviderResponses();
    $this->post(route('google.start'));
    $this->artisan('kody:google-attempts-prune')->assertSuccessful();
    expect(DB::table('google_auth_attempts')->count())->toBe(1);
    DB::table('google_auth_attempts')->update(['created_at' => now()->subDays(3), 'expires_at' => now()->subDays(2)]);
    $this->artisan('kody:google-attempts-prune')->assertSuccessful();
    expect(DB::table('google_auth_attempts')->count())->toBe(0);
});

test('A06 Google linking and unlinking roll back identities account state and audit together', function (string $operation) {
    googleProviderResponses();
    $user = User::factory()->create();
    if ($operation === 'unlink') {
        googleIdentity($user);
    }
    moduleSignIn($this, $user);
    $before = $user->fresh()->only(['profile_version', 'active_session_hash', 'remember_token']);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit unavailable')));
    if ($operation === 'link') {
        $this->post(route('google.link'), googleLinkData($user));
        googleCallback($this)->assertRedirect(route('account.google'));
        expect(DB::table('google_identities')->count())->toBe(0);
    } else {
        $request = Request::create('/account/google/unlink', 'POST');
        $request->setLaravelSession(session()->driver());
        $request->setUserResolver(fn () => $user);
        expect(fn () => app(GoogleAuthentication::class)->unlink($request, googleLinkData($user)))->toThrow(RuntimeException::class);
        expect(app(GoogleAuthentication::class)->state($user)['linked'])->toBeTrue();
    }
    expect($user->fresh()->only(array_keys($before)))->toBe($before);
})->with(['link', 'unlink']);

test('A06 account deletion removes Google identities and pending OAuth records', function () {
    googleProviderResponses();
    $user = moduleAccount(Role::Learner);
    googleIdentity($user);
    moduleSignIn($this, $user);
    $this->post(route('google.link'), googleLinkData($user));
    $this->post(route('account.delete.store'), deletionData($user))->assertRedirect(route('login'));
    expect(DB::table('google_identities')->count())->toBe(0)->and(DB::table('google_auth_attempts')->count())->toBe(0);
    $this->assertGuest();
});

test('A06 sensitive security changes cancel pending Google linking without removing established identity', function () {
    googleProviderResponses();
    $user = User::factory()->create();
    googleIdentity($user);
    moduleSignIn($this, $user);
    $this->post(route('google.link'), googleLinkData($user));
    DB::transaction(function () use ($user): void {
        $locked = User::whereKey($user->id)->lockForUpdate()->first();
        app(AccountSecurity::class)->revoke($locked);
        $locked->save();
    });
    expect(DB::table('google_auth_attempts')->value('consumed_at'))->not->toBeNull()->and(app(GoogleAuthentication::class)->state($user)['linked'])->toBeTrue();
    googleCallback($this);
    expect(DB::table('google_identities')->value('record_version'))->toBe(1);
});

test('A03 cancelled Google consent consumes state without provider exchange', function () {
    [$history] = googleProviderResponses();
    $this->post(route('google.start'));
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('google.callback', ['state' => session('state'), 'error' => 'access_denied']))->assertRedirect(route('login'));
    expect(count($history))->toBe(0)->and(DB::table('google_auth_attempts')->value('consumed_at'))->not->toBeNull();
});

test('A03 callback throttling never stores authorization query secrets', function () {
    googleProviderResponses();
    $this->post(route('google.start'));
    $this->withCookie(config('session.cookie'), session()->getId());
    foreach (range(1, 20) as $attempt) {
        $this->get(route('google.callback', ['code' => 'test-code', 'state' => 'invalid']))->assertRedirect(route('login'));
    }
    $this->get(route('google.callback', ['code' => 'test-code', 'state' => 'invalid']))->assertTooManyRequests();
    expect(json_encode(session()->all()))->not->toContain('test-code');
});

test('A06 Google management mutations are throttled and protected by csrf', function () {
    googleProviderResponses();
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('google.link'), [])->assertUnprocessable();
    }
    $this->postJson(route('google.link'), [])->assertTooManyRequests();
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('google.unlink'), googleLinkData($user))->assertStatus(419);
    expect(DB::table('google_identities')->count())->toBe(0);
});

test('A03 database constraints reject malformed Google identity and OAuth ownership records', function (string $case) {
    $user = User::factory()->create();
    expect(fn () => DB::transaction(function () use ($user, $case): void {
        if ($case === 'identity') {
            DB::table('google_identities')->insert(['user_id' => $user->id, 'subject_hash' => 'invalid', 'record_version' => 1, 'linked_at' => now()]);
        } else {
            DB::table('google_auth_attempts')->insert(['id' => (string) Str::uuid(), 'session_hash' => hash('sha256', 'session'),
                'state_hash' => hash('sha256', 'state'), 'intent' => 'link', 'user_id' => $user->id, 'created_at' => now(), 'expires_at' => now()->addMinutes(5)]);
        }
    }))->toThrow(QueryException::class);
})->with(['identity', 'attempt']);
