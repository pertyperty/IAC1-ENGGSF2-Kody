<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Content\CourseLearning;
use App\Services\Engagement\PlatformDashboard;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('B01 dashboard consolidates owned progress recent learning and unread notices', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id);
    app(CourseLearning::class)->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin());
    $other = moduleAccount(Role::Learner);
    $otherCourse = courseFixture(true, overrides: ['title' => 'Other learner private journey']);
    joinCourse($otherCourse, $other);
    moduleSignIn($this, $user);
    $this->get(route('dashboard'))->assertOk()->assertSee('Garden journey')->assertSee('1 of 1 adventures completed')
        ->assertSee('Pick up where you left off')->assertSee('0 unread')->assertDontSee('Other learner private journey')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->assertDatabaseCount('learning_activity_days', 1);
});

test('B01 dashboard hides withdrawn lesson details and preserves saved progress counts', function () {
    $course = courseFixture(true, overrides: ['title' => 'Hidden course title']);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id);
    $course->forceFill(['staff_withdrawn_at' => now()])->save();
    moduleSignIn($this, $user);
    $this->get(route('dashboard'))->assertOk()->assertDontSee('Hidden course title')->assertDontSee('Robot picnic')
        ->assertSee('Your progress is retained');
});

test('B01 creator summary scopes publication and pending counts to the current owner', function () {
    $module = moduleFixture(true);
    moduleFixture(false, ['title' => 'Someone else unpublished lesson']);
    $owner = User::findOrFail($module->created_by);
    moduleSignIn($this, $owner);
    $this->get(route('dashboard'))->assertOk()->assertSee('Your creator studio')->assertSee('1 published')
        ->assertSee('1 unread')->assertDontSee('Someone else unpublished lesson');
    $snapshot = app(PlatformDashboard::class)->snapshot($owner);
    expect($snapshot['creator'])->toHaveCount(3)->and($snapshot['creator'][0]['published'])->toBe(1)
        ->and($snapshot['updates'])->toHaveCount(1);
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Contributor]));
    $contributor = app(PlatformDashboard::class)->snapshot(auth()->user());
    expect($contributor['creator'])->toHaveCount(1)->and($contributor['creator'][0]['label'])->toBe('Coding quests');
});

test('B01 dashboard has bounded panels and constant query count as enrollments grow', function () {
    $user = moduleAccount(Role::Learner);
    for ($index = 0; $index < 8; $index++) {
        joinCourse(courseFixture(true, overrides: ['title' => 'Journey '.$index]), $user);
    }
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        $queries[] = $event->sql;
    });
    $snapshot = app(PlatformDashboard::class)->snapshot($user);
    expect($snapshot['courses'])->toHaveCount(4)->and($snapshot['activity'])->toHaveCount(0)->and(count($queries))->toBeLessThanOrEqual(8);
    foreach ($snapshot['courses'] as $enrollment) {
        expect((int) $enrollment->total_lessons)->toBe(1)->and((int) $enrollment->completed_lessons)->toBe(0);
    }
});

test('B01 dashboard rejects guests and suspended sessions while staff retain their notice view', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->get(route('dashboard'))->assertOk()->assertSee('Your updates')->assertDontSee('Your learning journeys')->assertDontSee('Your creator studio');
});
