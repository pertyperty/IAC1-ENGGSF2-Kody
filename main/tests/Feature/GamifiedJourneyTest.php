<?php

use App\Enums\Role;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Content\CourseLearning;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\TowerProgression;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('one Tower continuation is shown only to learners without granting progress or rewards', function (Role $role) {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount($role);
    moduleSignIn($this, $user);
    $response = $this->get(route('dashboard'))->assertOk();
    if ($role === Role::Learner) {
        $response->assertSee('Your next Tower level')->assertSee('Continue level 1')->assertSee('Optional starter lessons');
        expect(substr_count($response->getContent(), 'data-primary-continue'))->toBe(1);
    } else {
        $response->assertDontSee('data-primary-continue', false);
    }
    $response->assertDontSee('Your mission board')->assertDontSee('Your ladder');
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('xp_awards', 0);
})->with(Role::cases());

test('continuation uses the selected Tower track and never mistakes starter history for Tower clears', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    app(LearningProgression::class)->record($user, 'module-test-session', 'sequences', 'game', courseWin());
    moduleSignIn($this, $user);
    $this->get(route('home', ['track' => 'expert']))->assertOk();
    $this->get(route('dashboard'))->assertOk()->assertSee('Continue level 71');
    $service = app(TowerProgression::class);
    $next = $service->continuation($user->id, 'expert');
    expect($next['completed'])->toBe(0)->and($next['total'])->toBe(10);
    $level = TowerLevel::where('position', 71)->firstOrFail();
    DB::table('tower_clearances')->insert(['user_id' => $user->id, 'level_id' => $level->id, 'revision_id' => $level->current_revision_id, 'completed_at' => now()]);
    expect($service->continuation($user->id, 'expert')['next']['position'])->toBe(72);
    expect($service->continuation(moduleAccount(Role::Learner)->id, 'expert')['next']['position'])->toBe(71);
    expect($service->continuation($user->id, 'forged')['next']['position'])->toBe(1);
    $this->assertDatabaseCount('xp_awards', 1);
});

test('empty and completed tracks have an explicit map action without inventing another next level', function () {
    $user = moduleAccount(Role::Learner);
    expect(app(TowerProgression::class)->continuation($user->id, 'expert'))->toMatchArray(['total' => 0, 'completed' => 0, 'next' => null]);
    $this->seed(TowerLevelSeeder::class);
    foreach (TowerLevel::where('skill_track', 'expert')->get() as $level) {
        DB::table('tower_clearances')->insert(['user_id' => $user->id, 'level_id' => $level->id, 'revision_id' => $level->current_revision_id, 'completed_at' => now()]);
    }
    expect(app(TowerProgression::class)->continuation($user->id, 'expert'))->toMatchArray(['total' => 10, 'completed' => 10, 'next' => null]);
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
    $this->assertDatabaseCount('xp_awards', 1);
    expect(app(Achievements::class)->snapshot($user->id)['xp'])->toBe(40);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->postJson(route('course-learning.game', [$course, $game->id]), ['program' => ['right']])->assertUnprocessable()->assertJsonMissingPath('journey');
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertOk()
        ->assertJsonPath('journey.course_id', $course->id)->assertJsonPath('journey.completed', 2)
        ->assertJsonPath('journey.finished', true)->assertJsonPath('journey.next', null);
    $this->get(route('course-learning.lesson', [$course, $game->id]))->assertOk()->assertSee('Journey cleared!')->assertSee('Find another journey');
    $this->assertDatabaseCount('xp_awards', 2);
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
