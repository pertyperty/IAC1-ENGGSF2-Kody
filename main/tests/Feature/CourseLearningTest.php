<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\CourseEnrollment;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function courseWin(): array
{
    return ['program' => ['right', 'right', 'up', 'right', 'right']];
}

function joinCourse(LearningCourse $course, ?User $user = null): CourseEnrollment
{
    return app(CourseLearning::class)->enroll($user ?? moduleAccount(Role::Learner), 'module-test-session', $course->id, $course->published_revision_id);
}

test('B01 course catalog exposes only current Published approved metadata with bounded literal search', function () {
    $course = courseFixture(true);
    courseFixture(false, overrides: ['title' => 'Private course']);
    $this->get(route('course-learning.catalog'))->assertOk()->assertSee('Garden journey')->assertDontSee('Private course');
    $this->get(route('course-learning.catalog', ['q' => 'garden']))->assertSee('Garden journey');
    $this->get(route('course-learning.catalog', ['q' => '%']))->assertDontSee('Garden journey');
    $this->getJson(route('course-learning.catalog', ['q' => str_repeat('x', 81)]))->assertUnprocessable();
    $this->get(route('learning.catalog'))->assertSee(route('course-learning.catalog'));
    $this->get(route('course-learning.show', $course))->assertRedirect(route('login'));
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect(route('login'));
    $this->assertDatabaseCount('course_enrollments', 0);
});

test('B03 Learner Contributor and Instructor confirm one free enrollment and get dashboard and lesson access', function (Role $role) {
    $course = courseFixture(true);
    $slot = $course->publishedRevision->modules->sole();
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('course-learning.show', $course))->assertOk()->assertSee('Join this journey')->assertDontSee('data-completion-url', false);
    $this->assertDatabaseCount('course_enrollments', 0);
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id, 'user_id' => 999, 'access' => 'paid', 'price' => 999])->assertRedirect(route('course-learning.show', $course));
    $enrollment = CourseEnrollment::sole();
    expect($enrollment->user_id)->toBe($user->id)->and($enrollment->course_revision_id)->toBe($course->published_revision_id);
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect();
    $this->assertDatabaseCount('course_enrollments', 1);
    expect(DB::table('audit_events')->where('event', 'course.enrolled')->count())->toBe(1);
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Garden journey')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('dashboard'))->assertOk()->assertSee(route('course-learning.mine'));
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk()->assertSee(route('course-learning.game', [$course, $slot->id]))->assertHeader('Cache-Control', 'no-store, private');
    $this->assertDatabaseCount('course_module_progress', 1);
    $this->get(route('course-learning.show', $course))->assertSee('Continue your adventure');
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('B03 reviewer roles cannot enroll or consume learner course access', function (Role $role) {
    $course = courseFixture(true);
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->postJson(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertForbidden();
    $this->get(route('course-learning.show', $course))->assertForbidden();
    $this->get(route('course-learning.mine'))->assertForbidden();
    $this->assertDatabaseCount('course_enrollments', 0);
})->with([Role::Moderator, Role::Administrator]);

test('B03 enrollment rejects Draft archived and stale course revisions', function () {
    $draft = courseFixture();
    $course = courseFixture(true, overrides: ['title' => 'Published journey']);
    moduleSignIn($this, User::factory()->create());
    $this->get(route('course-learning.show', $draft))->assertNotFound();
    $this->postJson(route('course-learning.enroll', $draft), ['revision_id' => $draft->latestRevision->id])->assertConflict();
    $this->postJson(route('course-learning.enroll', $course), ['revision_id' => 999])->assertConflict();
    app(CoursePublishing::class)->archive(User::find($course->created_by), 'module-test-session', $course, 3);
    $this->get(route('course-learning.show', $course))->assertNotFound();
    $this->postJson(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertConflict();
    $this->assertDatabaseCount('course_enrollments', 0);
});

test('B03 enrollment rechecks archived modules and rolls back when audit persistence fails', function () {
    $course = courseFixture(true);
    $module = $course->publishedRevision->modules->sole()->module;
    app(ModulePublishing::class)->archive(User::find($module->created_by), 'module-test-session', $module, 3);
    moduleSignIn($this, User::factory()->create());
    $this->postJson(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertUnprocessable();
    $this->assertDatabaseCount('course_enrollments', 0);
    LearningModule::whereKey($module->id)->update(['status' => 'Published']);
    $user = moduleAccount(Role::Learner);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => joinCourse($course, $user))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('course_enrollments', 0);
});

test('B04 lessons and wins require current owned enrollment and reject cross course slot IDs', function () {
    $course = courseFixture(true);
    $other = courseFixture(true, overrides: ['title' => 'Other course']);
    $slot = $course->publishedRevision->modules->sole();
    $otherSlot = $other->publishedRevision->modules->sole();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertForbidden();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertForbidden();
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect();
    $this->get(route('course-learning.lesson', [$course, $otherSlot->id]))->assertNotFound();
    $this->postJson(route('course-learning.game', [$course, $otherSlot->id]), courseWin())->assertNotFound();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('course-learning.mine'))->assertDontSee('Garden journey');
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertForbidden();
    $this->assertDatabaseCount('course_module_progress', 0);
});

test('B04 pinned lessons survive later approved module and course revisions', function () {
    $course = courseFixture(true);
    $enrollment = joinCourse($course);
    $owner = User::find($course->created_by);
    $slot = $course->publishedRevision->modules->sole();
    $module = $slot->module;
    $modules = app(ModulePublishing::class);
    $modules->save($owner, 'module-test-session', moduleData(['record_version' => 3, 'title' => 'Updated adventure']), $module);
    $modules->submit($owner, 'module-test-session', $module->fresh(), 4);
    $modules->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 5, 'Approved', null);
    $courses = app(CoursePublishing::class);
    $courses->save($owner, 'module-test-session', courseData([$module->id], ['record_version' => 3, 'title' => 'Updated journey']), $course);
    $courses->submit($owner, 'module-test-session', $course->fresh(), 4);
    $courses->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 5, 'Approved', null);
    moduleSignIn($this, User::find($enrollment->user_id));
    $this->get(route('course-learning.show', $course))->assertSee('Garden journey')->assertDontSee('Updated journey');
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk()->assertDontSee('Updated adventure');
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertOk();
    $newSlot = $course->fresh()->publishedRevision->modules->sole();
    $this->get(route('course-learning.lesson', [$course, $newSlot->id]))->assertNotFound();
    $newEnrollment = joinCourse($course->fresh());
    expect($newEnrollment->course_revision_id)->not->toBe($enrollment->course_revision_id);
});

test('B04 server validated wins save course progress and shared streak once without clearing the starter ladder', function () {
    $course = courseFixture(true);
    $slot = $course->publishedRevision->modules->sole();
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id]);
    $this->postJson(route('course-learning.game', [$course, $slot->id]), ['program' => ['right'], 'completed' => true])->assertUnprocessable();
    $this->assertDatabaseCount('course_module_progress', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->postJson(route('course-learning.quiz', [$course, $slot->id]), ['answer' => 'a'])->assertNotFound();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin() + ['streak' => 999, 'user_id' => 999])->assertOk();
    $first = DB::table('course_module_progress')->sole();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertOk();
    expect(DB::table('course_module_progress')->sole()->completed_at)->toBe($first->completed_at);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'current_streak' => 1]);
    $this->get(route('course-learning.show', $course))->assertSee('Cleared');
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertSee('already cleared');
});

test('B04 quiz assessments validate the pinned answer and save completion', function () {
    $module = moduleFixture(true, moduleData(['assessment_kind' => 'quiz']));
    $course = courseFixture(true, $module);
    $slot = $course->publishedRevision->modules->sole();
    moduleSignIn($this, User::factory()->create());
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id]);
    $answer = $slot->revision->assessment['answer'];
    $this->postJson(route('course-learning.quiz', [$course, $slot->id]), ['answer' => $answer === 'a' ? 'b' : 'a'])->assertUnprocessable();
    $this->postJson(route('course-learning.quiz', [$course, $slot->id]), ['answer' => $answer])->assertOk();
    expect(DB::table('course_module_progress')->sole()->completed_at)->not->toBeNull();
});

test('D08 confirmed archive preserves structure progress and existing access while hiding public availability', function () {
    $course = courseFixture(true);
    $enrollment = joinCourse($course);
    $slot = $course->publishedRevision->modules->sole();
    moduleSignIn($this, User::find($course->created_by));
    $this->get(route('courses.archive-confirmation', $course))->assertOk()->assertSee('Existing learners keep access');
    $this->postJson(route('courses.archive', $course), ['record_version' => 3])->assertUnprocessable();
    $this->postJson(route('courses.archive', $course), ['record_version' => 2, 'confirmed' => true])->assertUnprocessable();
    $this->post(route('courses.archive', $course), ['record_version' => 3, 'confirmed' => true])->assertRedirect(route('courses.edit', $course));
    expect($course->fresh()->status)->toBe('Archived')->and($course->fresh()->record_version)->toBe(4);
    $this->assertDatabaseCount('course_revisions', 1);
    $this->assertDatabaseCount('course_revision_modules', 1);
    $this->assertDatabaseCount('course_enrollments', 1);
    $this->assertDatabaseHas('audit_events', ['event' => 'course.archived']);
    $this->get(route('courses.edit', $course))->assertOk()->assertSee('course is archived');
    $this->putJson(route('courses.update', $course), courseData([$slot->module_id], ['record_version' => 4]))->assertForbidden();
    $this->postJson(route('courses.submit', $course), ['record_version' => 4])->assertForbidden();
    $this->get(route('course-learning.catalog'))->assertDontSee('Garden journey');
    moduleSignIn($this, User::find($enrollment->user_id));
    $this->get(route('course-learning.mine'))->assertSee('Your access is retained');
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertOk();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('course-learning.show', $course))->assertNotFound();
    $this->postJson(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertConflict();
    $this->assertDatabaseCount('course_enrollments', 1);
});

test('D08 only the owning Instructor can archive Published courses', function (Role $role) {
    $course = courseFixture(true);
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('courses.archive-confirmation', $course))->assertForbidden();
    $this->postJson(route('courses.archive', $course), ['record_version' => 3, 'confirmed' => true])->assertForbidden();
    expect($course->fresh()->status)->toBe('Published');
})->with(Role::cases());

test('D03 archived individual modules remain unavailable inside retained courses', function () {
    $course = courseFixture(true);
    $enrollment = joinCourse($course);
    $slot = $course->publishedRevision->modules->sole();
    $owner = User::find($course->created_by);
    app(CoursePublishing::class)->archive($owner, 'module-test-session', $course, 3);
    app(ModulePublishing::class)->archive($owner, 'module-test-session', $slot->module, 3);
    moduleSignIn($this, User::find($enrollment->user_id));
    $this->get(route('course-learning.show', $course))->assertOk()->assertSee('currently unavailable');
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertNotFound();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertNotFound();
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('B03 B04 sensitive writes recheck fresh account eligibility session ownership and expiry', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    User::whereKey($user->id)->update(['account_status' => AccountStatus::Suspended]);
    expect(fn () => joinCourse($course, $user))->toThrow(AuthorizationException::class);
    User::whereKey($user->id)->update(['account_status' => AccountStatus::Active, 'active_session_hash' => hash('sha256', 'replacement')]);
    expect(fn () => joinCourse($course, $user))->toThrow(AuthorizationException::class);
    User::whereKey($user->id)->update(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->subMinute()]);
    expect(fn () => joinCourse($course, $user))->toThrow(AuthorizationException::class);
    User::whereKey($user->id)->update(['active_session_expires_at' => now()->addHour(), 'email_verified_at' => null, 'account_status' => AccountStatus::Unverified]);
    expect(fn () => joinCourse($course, $user))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('course_enrollments', 0);
});

test('B03 PostgreSQL enrollment uniqueness and cross course revision constraints enforce grants', function () {
    $course = courseFixture(true);
    $enrollment = joinCourse($course);
    $other = courseFixture(true, overrides: ['title' => 'Other course']);
    expect(fn () => DB::transaction(fn () => CourseEnrollment::create(['user_id' => $enrollment->user_id, 'course_id' => $course->id, 'course_revision_id' => $course->published_revision_id, 'enrolled_at' => now()])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => CourseEnrollment::create(['user_id' => $enrollment->user_id, 'course_id' => $other->id, 'course_revision_id' => $course->published_revision_id, 'enrolled_at' => now()])))->toThrow(QueryException::class);
    $slot = $other->publishedRevision->modules->sole();
    expect(fn () => DB::transaction(fn () => DB::table('course_module_progress')->insert(['enrollment_id' => $enrollment->id, 'assignment_id' => $slot->id,
        'course_revision_id' => $enrollment->course_revision_id, 'first_accessed_at' => now(), 'last_accessed_at' => now()])))->toThrow(QueryException::class);
});

test('B04 a saved enrollment cannot bypass a later suspension or revoked session', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    User::whereKey($user->id)->update(['account_status' => AccountStatus::Suspended]);
    expect(fn () => app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id))->toThrow(AuthorizationException::class);
    expect(fn () => app(CourseLearning::class)->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin()))->toThrow(AuthorizationException::class);
    User::whereKey($user->id)->update(['account_status' => AccountStatus::Active, 'active_session_hash' => hash('sha256', 'replacement')]);
    expect(fn () => app(CourseLearning::class)->outline($user, 'module-test-session', $course->id))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('course_module_progress', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('D08 archiving a course with a pending replacement preserves revisions and removes the review queue entry', function () {
    $course = courseFixture(true);
    $owner = User::find($course->created_by);
    $slot = $course->publishedRevision->modules->sole();
    $publishing = app(CoursePublishing::class);
    $publishing->save($owner, 'module-test-session', courseData([$slot->module_id], ['record_version' => 3]), $course);
    $publishing->submit($owner, 'module-test-session', $course->fresh(), 4);
    $publishing->archive($owner, 'module-test-session', $course->fresh(), 5);
    expect($course->fresh()->latestRevision->review_status)->toBe('Pending');
    $this->assertDatabaseCount('course_revisions', 2);
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->get(route('course-reviews.index'))->assertDontSee('Garden journey');
    $this->postJson(route('course-reviews.review', $course), ['record_version' => 6, 'decision' => 'Approved'])->assertUnprocessable();
    expect($course->fresh()->status)->toBe('Archived');
});

test('B03 D08 mutations require CSRF and canonical route IDs', function () {
    $course = courseFixture(true);
    moduleSignIn($this, User::find($course->created_by));
    $this->app['env'] = 'local';
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertStatus(419);
    $this->post(route('courses.archive', $course), ['record_version' => 3, 'confirmed' => true])->assertStatus(419);
    $this->get('/learn/courses/invalid-id')->assertNotFound();
    $this->assertDatabaseCount('course_enrollments', 0);
});
