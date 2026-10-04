<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Account\ContributorEligibility;
use App\Services\Administration\AuditRecorder;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function pathFixture(): array
{
    $reading = moduleFixture(true, ['assessment_kind' => 'none', 'type' => 'Article', 'title' => 'First read']);
    $owner = User::findOrFail($reading->created_by);
    $moduleService = app(ModulePublishing::class);
    $game = $moduleService->save($owner, 'module-test-session', moduleData(['title' => 'Then play']));
    $moduleService->submit($owner, 'module-test-session', $game, 1);
    $moduleService->review(moduleAccount(Role::Moderator), 'module-test-session', $game->fresh(), 2, 'Approved', null);
    $courseService = app(CoursePublishing::class);
    $course = $courseService->save($owner, 'module-test-session', courseData([$reading->id, $game->id], ['sequential' => true]));
    $courseService->submit($owner, 'module-test-session', $course, 1);
    $courseService->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 2, 'Approved', null);

    return [$course->fresh(), $reading, $game->fresh()];
}

test('B03 B04 approved sequential paths unlock only after explicit reading or validated wins', function () {
    [$course] = pathFixture();
    [$reading, $game] = $course->publishedRevision->modules->all();
    $learner = User::factory()->create();
    moduleSignIn($this, $learner);
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id, 'sequential' => false])->assertRedirect();
    $this->get(route('course-learning.show', $course))->assertOk()->assertSee('Locked')->assertSee('0 of 2 adventures completed');
    $this->get(route('course-learning.lesson', [$course, $game->id]))->assertForbidden();
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertForbidden();
    $this->postJson(route('course-learning.read', [$course, $reading->id]), ['confirmed' => true])->assertConflict();
    $this->get(route('course-learning.lesson', [$course, $reading->id]))->assertOk()->assertSee('Mark as read');
    expect(app(ContributorEligibility::class)->snapshot($learner)['completed_modules_count'])->toBe(0);
    $this->get(route('course-learning.lesson', [$course, $game->id]))->assertForbidden();
    $this->postJson(route('course-learning.read', [$course, $reading->id]), [])->assertUnprocessable();
    $this->post(route('course-learning.read', [$course, $reading->id]), ['confirmed' => true])->assertRedirect(route('course-learning.show', $course));
    $first = DB::table('course_module_progress')->sole();
    $this->post(route('course-learning.read', [$course, $reading->id]), ['confirmed' => true])->assertRedirect();
    expect(DB::table('course_module_progress')->sole()->completed_at)->toBe($first->completed_at);
    expect(DB::table('audit_events')->where('event', 'course.lesson_read')->count())->toBe(1);
    expect(app(ContributorEligibility::class)->snapshot($learner)['completed_modules_count'])->toBe(0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('learning_progress', 0);
    $this->get(route('course-learning.lesson', [$course, $game->id]))->assertOk();
    $this->postJson(route('course-learning.read', [$course, $game->id]), ['confirmed' => true])->assertConflict();
    $this->postJson(route('course-learning.game', [$course, $game->id]), ['program' => ['right']])->assertUnprocessable();
    $this->get(route('course-learning.show', $course))->assertSee('1 of 2 adventures completed');
    $this->postJson(route('course-learning.game', [$course, $game->id]), courseWin())->assertOk();
    $this->get(route('course-learning.show', $course))->assertSee('2 of 2 adventures completed');
    expect(app(ContributorEligibility::class)->snapshot($learner)['completed_modules_count'])->toBe(1);
});

test('B04 reading completion rechecks ownership status withdrawal and archived modules', function () {
    [$course, $module, $game] = pathFixture();
    $slot = $course->publishedRevision->modules->first();
    $enrollment = joinCourse($course);
    moduleSignIn($this, User::findOrFail($enrollment->user_id));
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk();
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $game->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->get(route('course-learning.show', $course))->assertOk()->assertDontSee('First read')->assertDontSee('Then play')->assertSee('Adventure unavailable');
    $this->postJson(route('course-learning.read', [$course, $slot->id]), ['confirmed' => true])->assertNotFound();
    $module->forceFill(['staff_withdrawn_at' => null, 'status' => 'Archived'])->save();
    $this->postJson(route('course-learning.read', [$course, $slot->id]), ['confirmed' => true])->assertNotFound();
    $module->update(['status' => 'Published']);
    moduleSignIn($this, User::factory()->create());
    $this->postJson(route('course-learning.read', [$course, $slot->id]), ['confirmed' => true])->assertForbidden();
    expect(DB::table('course_module_progress')->sole()->completed_at)->toBeNull();
});

test('D05 B03 course replacement preserves prior open enrollments and snapshots new sequential access', function () {
    $course = courseFixture(true);
    $existing = joinCourse($course);
    expect($existing->sequential)->toBeFalse();
    $owner = User::findOrFail($course->created_by);
    $service = app(CoursePublishing::class);
    $moduleId = $course->publishedRevision->modules->sole()->module_id;
    $service->save($owner, 'module-test-session', courseData([$moduleId], ['record_version' => 3, 'sequential' => true]), $course);
    expect($course->fresh()->publishedRevision->sequential)->toBeFalse();
    $service->submit($owner, 'module-test-session', $course->fresh(), 4);
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 5, 'Approved', null);
    $new = joinCourse($course->fresh());
    expect($new->sequential)->toBeTrue()->and($existing->fresh()->sequential)->toBeFalse();
});

test('B04 reading completion rolls back with a failed durable audit', function () {
    [$course] = pathFixture();
    $user = moduleAccount(Role::Learner);
    $enrollment = joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->first();
    app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => app(CourseLearning::class)->markRead($user, 'module-test-session', $course->id, $slot->id))->toThrow(RuntimeException::class);
    expect(DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->sole()->completed_at)->toBeNull();
});

test('course path rollback refuses retained policy rather than silently changing access', function () {
    pathFixture();
    $migration = require database_path('migrations/2026_10_04_000030_add_course_learning_paths.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Preserve learning paths');
    expect(Schema::hasColumn('course_enrollments', 'sequential'))->toBeTrue();
});

test('D06 staff review explicitly shows the learning path policy', function () {
    [$course] = pathFixture();
    moduleSignIn($this, moduleAccount(Role::Moderator));
    $this->get(route('course-reviews.show', $course))->assertOk()->assertSee('Sequential path:')->assertSee('Existing enrollments keep their original access policy.');
});
