<?php

use App\Enums\Role;
use App\Services\Account\ContributorEligibility;
use App\Services\Content\CourseLearning;
use App\Services\Gamification\Achievements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('B05 article completion requires an authorized opening and grants module XP once without assessed credit', function () {
    $module = moduleFixture(true, ['type' => 'Article', 'assessment_kind' => 'none']);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $url = route('modules.read', [$module, $module->published_revision_id]);
    $this->post($url, ['confirmed' => true])->assertConflict();
    $this->get(route('modules.show', $module))->assertOk()->assertSee('Mark as read');
    $this->post($url, [])->assertSessionHasErrors('confirmed');
    $this->post($url, ['confirmed' => true])->assertRedirect();
    $this->post($url, ['confirmed' => true])->assertRedirect();
    expect(app(Achievements::class)->snapshot($user->id)['xp'])->toBe(40);
    $this->assertDatabaseCount('module_read_completions', 1);
    $this->assertDatabaseCount('xp_awards', 1);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    expect(app(ContributorEligibility::class)->snapshot($user)['completed_modules_count'])->toBe(0);
    $this->get(route('learning.catalog'))->assertSee('Completed · article read');
});

test('B04 course and standalone article completion share one module reward', function () {
    $module = moduleFixture(true, ['type' => 'Article', 'assessment_kind' => 'none']);
    $course = courseFixture(true, $module);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $slot = $course->publishedRevision->modules->first();
    app(CourseLearning::class)->enroll($user->fresh(), session()->getId(), $course->id, $course->published_revision_id);
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk();
    $this->post(route('course-learning.read', [$course, $slot->id]), ['confirmed' => true])->assertRedirect();
    $this->get(route('modules.show', $module))->assertOk();
    $this->post(route('modules.read', [$module, $module->published_revision_id]), ['confirmed' => true])->assertRedirect();
    expect(app(Achievements::class)->snapshot($user->id)['xp'])->toBe(40);
    expect(DB::table('course_module_progress')->sole()->completed_at)->not->toBeNull();
    $this->assertDatabaseCount('module_read_completions', 1);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('B05 reading cannot bypass assessment revisions or staff withdrawal', function () {
    $module = moduleFixture(true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('modules.show', $module))->assertOk();
    $this->post(route('modules.read', [$module, $module->published_revision_id]), ['confirmed' => true])->assertConflict();
    $this->assertDatabaseCount('xp_awards', 0);
    $article = moduleFixture(true, ['type' => 'Article', 'assessment_kind' => 'none']);
    $this->get(route('modules.show', $article))->assertOk();
    $article->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->post(route('modules.read', [$article, $article->published_revision_id]), ['confirmed' => true])->assertNotFound();
    $this->assertDatabaseCount('module_read_completions', 0);
});
