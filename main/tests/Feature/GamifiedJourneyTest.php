<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Content\CourseLearning;
use App\Services\Engagement\LearnerMissions;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('mission board shows owned saved goals only to participant roles without granting rewards', function (Role $role) {
    $user = moduleAccount($role);
    moduleSignIn($this, $user);
    $response = $this->get(route('dashboard'))->assertOk();
    if ($role === Role::Learner) {
        $response->assertSee('Your mission board')->assertSee('Save a daily win')->assertSee('Grow toward Explorer');
    } else {
        $response->assertDontSee('Your mission board');
    }
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('xp_awards', 0);
})->with(Role::cases());

test('mission goals reflect saved daily wins and approved rank thresholds and expire on Manila dates', function () {
    $this->travelTo(now()->setTimezone('Asia/Manila')->startOfDay()->addHours(12));
    $user = moduleAccount(Role::Learner);
    app(LearningProgression::class)->record($user, 'module-test-session', 'sequences', 'game', courseWin());
    $goals = app(LearnerMissions::class)->snapshot(app(LearningProgression::class)->snapshot($user->id), app(Achievements::class)->snapshot($user->id));
    expect($goals[0]['done'])->toBeTrue()->and($goals[1]['value'])->toBe(1)
        ->and($goals[1]['url'])->toBe(route('learning.show', 'loops'))->and($goals[2]['max'])->toBe(200);
    $this->travel(1)->days();
    $goals = app(LearnerMissions::class)->snapshot(app(LearningProgression::class)->snapshot($user->id), app(Achievements::class)->fromXp(3000));
    expect($goals[0]['done'])->toBeFalse()->and($goals[2]['done'])->toBeTrue()->and($goals[2]['value'])->toBe(3000);
    $this->assertDatabaseCount('xp_awards', 1);
});

test('course trail continuation comes from committed reading and game progress and ends without a fabricated next link', function () {
    [$course] = pathFixture();
    [$reading, $game] = $course->publishedRevision->modules->all();
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect();
    $learning = app(CourseLearning::class);
    $trail = $learning->outline($user, session()->getId(), $course->id)['trail'];
    expect($trail['next']['id'])->toBe($reading->id)->and($trail['steps'][1]['state'])->toBe('Locked');
    $this->get(route('course-learning.lesson', [$course, $reading->id]))->assertOk();
    expect($learning->lesson($user, session()->getId(), $course->id, $reading->id)['trail']['next'])->toBeNull();
    $this->post(route('course-learning.read', [$course, $reading->id]), ['confirmed' => true])->assertRedirect();
    $trail = $learning->outline($user, session()->getId(), $course->id)['trail'];
    expect($trail['completed'])->toBe(1)->and($trail['next']['url'])->toBe(route('course-learning.lesson', [$course, $game->id]));
    $this->assertDatabaseCount('xp_awards', 0);
    $this->postJson(route('course-learning.game', [$course, $game->id]), ['program' => ['right']])->assertUnprocessable()->assertJsonMissingPath('journey');
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertOk()
        ->assertJsonPath('journey.course_id', $course->id)->assertJsonPath('journey.completed', 2)
        ->assertJsonPath('journey.finished', true)->assertJsonPath('journey.next', null);
    $this->get(route('course-learning.lesson', [$course, $game->id]))->assertOk()->assertSee('Journey cleared!')->assertSee('Find another journey');
    $this->assertDatabaseCount('xp_awards', 1);
});

test('unavailable course steps retain progress but do not expose withdrawn titles or recommend locked lessons', function () {
    [$course, $module] = pathFixture();
    $enrollment = joinCourse($course);
    $user = User::findOrFail($enrollment->user_id);
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $trail = app(CourseLearning::class)->outline($user, 'module-test-session', $course->id)['trail'];
    expect($trail['next'])->toBeNull()->and($trail['finished'])->toBeFalse()
        ->and($trail['steps'][0]['title'])->toBe('Adventure unavailable')->and($trail['steps'][1]['state'])->toBe('Locked');
    expect(DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->count())->toBe(0);
});

test('open paths recommend an unfinished available lesson while other accounts cannot use the continuation', function () {
    [$course] = pathFixture();
    $course->publishedRevision->forceFill(['sequential' => false])->save();
    [$reading, $game] = $course->publishedRevision->modules->all();
    $enrollment = joinCourse($course);
    $user = User::findOrFail($enrollment->user_id);
    moduleSignIn($this, $user);
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertOk()
        ->assertJsonPath('journey.finished', false)->assertJsonPath('journey.next.id', $reading->id);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('course-learning.lesson', [$course, $reading->id]))->assertForbidden();
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertForbidden()->assertJsonMissingPath('journey');
});
