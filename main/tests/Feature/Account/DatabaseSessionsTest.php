<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\ModeratorAppointments;
use App\Services\Administration\SupportProfileCorrections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('G02 support correction removes database sessions and the corrected username appears only after new login', function () {
    $target = User::factory()->create();
    $credentials = ['email' => $target->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $oldBrowser = session()->getId();
    $admin = moduleAccount(Role::Administrator);
    app(SupportProfileCorrections::class)->correct($admin, 'module-test-session', $target, supportCorrectionData($target));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: session()->getId())->assertOk()->assertSee('correctedplayer');
});

test('G02 appointment and removal revoke database sessions and old Moderator privileges', function () {
    $target = User::factory()->create();
    $credentials = ['email' => $target->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $oldBrowser = session()->getId();
    $admin = moduleAccount(Role::Administrator);
    $service = app(ModeratorAppointments::class);
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account-governance.index'), sessionId: $oldBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $moderatorBrowser = session()->getId();
    databaseBrowserRequest($this, 'get', route('account-governance.index'), sessionId: $moderatorBrowser)->assertOk();
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target, ['action' => 'Removed']));
    $this->assertDatabaseMissing('sessions', ['id' => $moderatorBrowser]);
    databaseBrowserRequest($this, 'get', route('account-governance.index'), sessionId: $moderatorBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    databaseBrowserRequest($this, 'get', route('account-governance.index'), sessionId: session()->getId())->assertForbidden();
});

test('G03 G04 remove target database sessions and reinstatement cannot restore an old browser', function () {
    $target = User::factory()->create();
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $target->email, 'password' => 'password']);
    $oldBrowser = session()->getId();
    $actor = moduleAccount(Role::Moderator);
    app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
    app(AccountEnforcement::class)->change($actor, 'module-test-session', $target, enforcementData($target, ['action' => 'Reinstated']));
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $target->email, 'password' => 'password'])->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
});

test('Delete Account removes authenticated database sessions and prevents old-browser access', function () {
    $user = User::factory()->create();
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $oldBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('account.delete.store'), deletionData($user), $oldBrowser)->assertRedirect(route('login'));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
});

test('A07 archival removes database sessions and the old browser cannot access its profile', function () {
    $user = User::factory()->create();
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $oldBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('account.archive.store'), archiveData($user), $oldBrowser)->assertRedirect(route('login'));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
});

test('A06 password editing deletes authenticated database sessions and rejects the old browser', function () {
    $user = User::factory()->create();
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $oldBrowser = session()->getId();
    databaseBrowserRequest($this, 'patch', route('account.update'), profileEditData($user, ['current_password' => 'password',
        'password' => 'StrongNewPass12!', 'password_confirmation' => 'StrongNewPass12!']), $oldBrowser)->assertRedirect(route('login'));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
});

test('A04 successful recovery removes authenticated database sessions and requires a new login', function () {
    $user = User::factory()->create();
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $oldBrowser = session()->getId();
    app(AccountRecoveryService::class)->request($user);
    $token = VerificationDelivery::sole()->token;
    databaseBrowserRequest($this, 'post', route('recovery.authorize'), ['recovery_token' => $token])->assertRedirect(route('recovery.reset'));
    $guestBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('recovery.complete'), ['password' => 'NewStrongPass12!', 'password_confirmation' => 'NewStrongPass12!'], $guestBrowser)
        ->assertRedirect(route('login'));
    $this->assertDatabaseMissing('sessions', ['id' => $oldBrowser]);
    expect($user->fresh()->active_session_hash)->toBeNull();
    databaseBrowserRequest($this, 'get', route('account.show'), sessionId: $oldBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'post', route('login.store'), ['email' => $user->email, 'password' => 'NewStrongPass12!'])->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
});

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
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $firstBrowser = session()->getId();
    $this->assertDatabaseHas('sessions', ['id' => $firstBrowser, 'user_id' => $user->id]);
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $firstBrowser)->assertOk();

    databaseBrowserRequest($this, 'post', route('login.store'), $credentials)->assertRedirect(route('login.confirmation'));
    $secondBrowser = session()->getId();
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBe(hash('sha256', $firstBrowser));
    databaseBrowserRequest($this, 'post', route('login.confirm'), ['choice' => 'continue'], $secondBrowser)->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
    $replacementBrowser = session()->getId();
    expect($replacementBrowser)->not->toBe($secondBrowser);
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $firstBrowser)->assertRedirect(route('login'));
    databaseBrowserRequest($this, 'get', route('dashboard'), sessionId: $replacementBrowser)->assertOk()->assertSee('View your profile');
});

test('A03 confirmation proof is bound to its guest browser and cancelling preserves the original browser', function () {
    $user = User::factory()->create();
    $credentials = ['email' => $user->email, 'password' => 'password'];
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $firstBrowser = session()->getId();
    databaseBrowserRequest($this, 'post', route('login.store'), $credentials);
    $pendingBrowser = session()->getId();

    databaseBrowserRequest($this, 'postJson', route('login.confirm'), ['choice' => 'continue'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Invalid sign-in details.');
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
