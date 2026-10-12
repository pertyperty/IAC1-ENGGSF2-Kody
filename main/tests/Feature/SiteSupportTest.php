<?php

use App\Enums\Role;
use App\Services\Administration\SiteManagement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function noticeData(array $overrides = []): array
{
    return array_replace(['title' => 'A little update', 'message' => 'Visit Help for a new guide.', 'destination' => 'help', 'active' => 1, 'record_version' => 1, 'current_password' => 'password', 'confirmed' => true], $overrides);
}

test('public support supplies direct email and appeal routes without sending or storing messages', function () {
    $this->get(route('contact'))->assertOk()->assertSee('kody.support@gmail.com')->assertSee('mailto:', false)->assertSee('Think your suspension was a mistake?')->assertDontSee('<form', false);
    $this->get(route('login'))->assertOk()->assertSee(route('contact').'#appeals', false);
    $this->get(route('welcome'))->assertOk()->assertSee('Small puzzles.')->assertSee('Explore courses');
    $this->assertDatabaseCount('verification_deliveries', 0);
    $this->assertDatabaseCount('jobs', 0);
});

test('Administrator notices require current credentials explicit confirmation and an unchanged version', function () {
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $admin);
    $this->putJson(route('site-management.update'), noticeData(['current_password' => 'wrong']))->assertUnprocessable();
    $this->putJson(route('site-management.update'), noticeData(['confirmed' => false]))->assertUnprocessable();
    $this->putJson(route('site-management.update'), noticeData(['destination' => 'https://external.test']))->assertUnprocessable();
    $this->assertDatabaseCount('audit_events', 0);
    $this->put(route('site-management.update'), noticeData())->assertRedirect(route('site-management.index'));
    $this->get(route('help.index'))->assertOk()->assertSee('A little update');
    $this->get(route('account.edit'))->assertOk()->assertDontSee('A little update');
    $this->putJson(route('site-management.update'), noticeData())->assertUnprocessable()->assertJsonValidationErrors('record_version');
    $this->put(route('site-management.update'), noticeData(['active' => 0, 'record_version' => 2]))->assertRedirect();
    $this->get(route('help.index'))->assertOk()->assertDontSee('A little update');
    expect(DB::table('audit_events')->where('event', 'site.announcement_updated')->count())->toBe(2);
    $audit = json_decode(DB::table('audit_events')->where('event', 'site.announcement_updated')->orderByDesc('created_at')->first()->context, true);
    expect($audit)->toHaveKeys(['before', 'after', 'version']);
});

test('site controls escape notices and expose operation aggregates without provider secrets or job payloads', function () {
    config(['judge0.rapidapi_key' => 'PRIVATE_TEST_API_KEY']);
    foreach (['ready', 'delayed', 'reserved'] as $state) {
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => 'PRIVATE_TEST_JOB_PAYLOAD', 'attempts' => 0,
            'available_at' => $state === 'delayed' ? now()->addHour()->timestamp : now()->timestamp,
            'reserved_at' => $state === 'reserved' ? now()->timestamp : null, 'created_at' => now()->timestamp]);
    }
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $admin);
    $this->put(route('site-management.update'), noticeData(['title' => '<script>unsafe()</script>']))->assertRedirect();
    $this->get(route('contact'))->assertOk()->assertSee('&lt;script&gt;unsafe()', false)->assertDontSee('<script>unsafe()', false);
    $this->get(route('site-management.index'))->assertOk()->assertSee('3 stored')->assertSee('1 ready · 1 delayed · 1 reserved')->assertDontSee('PRIVATE_TEST_API_KEY')->assertDontSee('PRIVATE_TEST_JOB_PAYLOAD');
});

test('non Administrators cannot manage announcements', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    $this->get(route('site-management.index'))->assertForbidden();
    $this->put(route('site-management.update'), noticeData())->assertForbidden();
    expect(DB::table('site_announcements')->sole()->record_version)->toBe(1);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('site actions reauthorize a revoked Administrator and a replaced session', function () {
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $admin);
    $sessionId = session()->getId();
    $admin->forceFill(['account_role' => Role::Learner])->save();
    expect(fn () => app(SiteManagement::class)->save($admin, $sessionId, noticeData()))
        ->toThrow(AuthorizationException::class);
    $admin->forceFill(['account_role' => Role::Administrator, 'active_session_hash' => hash('sha256', 'replacement-session')])->save();
    expect(fn () => app(SiteManagement::class)->save($admin, $sessionId, noticeData()))
        ->toThrow(AuthorizationException::class);
    expect(DB::table('site_announcements')->sole()->record_version)->toBe(1);
    $this->assertDatabaseCount('audit_events', 0);
});
