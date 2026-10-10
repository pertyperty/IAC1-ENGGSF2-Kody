<?php

use App\Enums\Role;
use App\Services\Gamification\Achievements;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shared navigation exposes progress only to participants and hides it during account editing', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    $response = $this->get(route('dashboard'))->assertOk();
    if ($role === Role::Learner) {
        $response->assertSee('data-progress-strip', false)->assertSee('data-progress-xp', false);
        $this->getJson(route('play.progress'))->assertOk()->assertJsonPath('achievements.xp', 0)
            ->assertJsonPath('progress.completed_count', 0)->assertHeader('Cache-Control', 'no-store, private');
    } else {
        $response->assertDontSee('data-progress-strip', false)->assertDontSee('Continue your learning journeys');
        $this->getJson(route('play.progress'))->assertStatus(in_array($role, [Role::Contributor, Role::Instructor], true) ? 200 : 403);
    }
    $this->get(route('account.edit'))->assertOk()->assertDontSee('data-progress-strip', false)
        ->assertSee('Back to my account');
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseCount('xp_awards', 0);
})->with(Role::cases());

test('guest account pages have an explicit home button and public courses never point back to private journeys', function () {
    foreach (['login', 'register', 'recovery.request', 'course-learning.catalog'] as $name) {
        $this->get(route($name))->assertOk()->assertSee('Back to home')->assertDontSee('Back to my journeys');
    }
    $this->get(route('play.progress'))->assertRedirect(route('login'));
});

test('feedback is escaped and displayed once in the shared dismissible notification layer', function () {
    $message = '<script>alert("unsafe")</script> Saved';
    $response = $this->withSession(['status' => $message])->get(route('login'))->assertOk()
        ->assertSee('data-flash-toast', false)->assertSee('Dismiss notification')->assertDontSee($message, false);
    expect(substr_count($response->getContent(), e($message)))->toBe(1);
});

test('progress refresh is scoped to the authenticated account and never accepts a browser supplied identity', function () {
    $other = moduleAccount(Role::Learner);
    app(Achievements::class)->award($other->id, 'test:other-account', 20);
    $current = moduleAccount(Role::Learner);
    moduleSignIn($this, $current);
    $this->getJson(route('play.progress', ['user_id' => $other->id, 'xp' => 9999]))->assertOk()
        ->assertJsonPath('achievements.xp', 0)->assertJsonPath('progress.current_streak', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseCount('xp_awards', 1);
});

test('staff course browsing leads directly to authorized review instead of a forbidden learner page', function (Role $role) {
    $course = courseFixture(true);
    moduleSignIn($this, moduleAccount($role));
    $this->get(route('course-learning.catalog'))->assertOk()->assertSee(route('course-reviews.show', $course))
        ->assertSee('Review this course')->assertDontSee(route('course-learning.show', $course))
        ->assertDontSee(route('course-learning.mine'));
    $this->get(route('course-reviews.show', $course))->assertOk();
    $this->get(route('course-learning.show', $course))->assertForbidden();
    $this->assertDatabaseCount('course_enrollments', 0);
})->with([Role::Moderator, Role::Administrator]);
