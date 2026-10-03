<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\CourseRevisionModule;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function courseData(array $moduleIds = [], array $overrides = []): array
{
    return array_replace(['record_version' => 1, 'title' => 'Garden journey', 'description' => 'Learn one little idea at a time.',
        'category' => 'Programming', 'difficulty' => 'Beginner', 'estimated_duration' => 2, 'module_ids' => $moduleIds], $overrides);
}

function courseFixture(bool $published = false, ?LearningModule $module = null, array $overrides = []): LearningCourse
{
    $module ??= moduleFixture(true);
    $owner = User::find($module->created_by);
    $publishing = app(CoursePublishing::class);
    $course = $publishing->save($owner, 'module-test-session', courseData([$module->id], $overrides));
    if ($published) {
        $publishing->submit($owner, 'module-test-session', $course, 1);
        $publishing->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 2, 'Approved', null);
    }

    return $course->fresh();
}

test('D05 instructors create owned course drafts with metadata and ordered approved modules', function () {
    $module = moduleFixture(true);
    $owner = User::find($module->created_by);
    $other = app(ModulePublishing::class)->save($owner, 'module-test-session', moduleData(['title' => 'Second adventure']));
    app(ModulePublishing::class)->submit($owner, 'module-test-session', $other, 1);
    app(ModulePublishing::class)->review(moduleAccount(Role::Moderator), 'module-test-session', $other->fresh(), 2, 'Approved', null);
    moduleSignIn($this, $owner);
    $this->get(route('courses.create'))->assertOk();
    $this->post(route('courses.store'), courseData([$other->id, '', $module->id], ['created_by' => 999, 'status' => 'Published', 'price' => 999]))->assertRedirect();
    $course = LearningCourse::sole();
    expect($course->created_by)->toBe($owner->id)->and($course->status)->toBe('Draft')->and($course->published_revision_id)->toBeNull();
    $slots = $course->latestRevision->modules;
    expect($slots->pluck('module_id')->all())->toBe([$other->id, $module->id])->and($slots->pluck('position')->all())->toBe([1, 2]);
    $this->get(route('courses.index'))->assertOk()->assertSee('Garden journey');
    $this->get(route('courses.edit', $course))->assertOk()->assertSee('Second adventure')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('courses.preview', [$course, $slots[0]->id]))->assertOk()->assertSee('Second adventure')->assertDontSee('data-completion-url', false);
});

test('D05 other roles cannot create courses or inspect the studio', function (Role $role) {
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('courses.index'))->assertForbidden();
    $this->postJson(route('courses.store'), courseData())->assertForbidden();
})->with([Role::Learner, Role::Contributor, Role::Moderator, Role::Administrator]);

test('D05 validates metadata and distinct bounded module selections', function (array $overrides, string $field) {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->postJson(route('courses.store'), courseData([], $overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('learning_courses', 0);
})->with([
    [['title' => str_repeat('x', 151)], 'title'], [['category' => str_repeat('x', 51)], 'category'],
    [['estimated_duration' => 0], 'estimated_duration'], [['difficulty' => 'Expert'], 'difficulty'],
    [['module_ids' => [1, 1]], 'module_ids.0'], [['module_ids' => ['bad']], 'module_ids.0'],
    [['module_ids' => 'bad'], 'module_ids'], [['module_ids' => array_fill(0, 101, 1)], 'module_ids'],
]);

test('D07 rejects foreign draft and archived modules atomically', function (string $state) {
    $module = moduleFixture($state !== 'Draft');
    $owner = $state === 'foreign' ? moduleAccount(Role::Instructor) : User::find($module->created_by);
    if ($state === 'Archived') {
        $module->update(['status' => 'Archived']);
    }
    moduleSignIn($this, $owner);
    $this->postJson(route('courses.store'), courseData([$module->id]))->assertUnprocessable()->assertJsonValidationErrors('module_ids');
    $this->assertDatabaseCount('learning_courses', 0);
    $this->assertDatabaseCount('course_revision_modules', 0);
})->with(['foreign', 'Draft', 'Archived']);

test('D06 prevents cross-owner edits submissions and preview access', function () {
    $course = courseFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->get(route('courses.edit', $course))->assertForbidden();
    $this->putJson(route('courses.update', $course), courseData())->assertForbidden();
    $this->postJson(route('courses.submit', $course), ['record_version' => 1])->assertForbidden();
    $this->get(route('courses.preview', [$course, $course->latestRevision->modules[0]->id]))->assertForbidden();
});

test('G06 course reviewers publish the exact saved revision and send one private update', function (Role $role) {
    $course = courseFixture();
    app(CoursePublishing::class)->submit(User::find($course->created_by), 'module-test-session', $course, 1);
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('course-reviews.index'))->assertOk()->assertSee('Garden journey');
    $this->get(route('course-reviews.show', $course))->assertOk()->assertSee('Robot picnic');
    $this->get(route('courses.preview', [$course, $course->latestRevision->modules[0]->id]))->assertOk()->assertDontSee('data-completion-url', false);
    $this->post(route('course-reviews.review', $course), ['record_version' => 2, 'decision' => 'Approved'])->assertRedirect();
    $this->postJson(route('course-reviews.review', $course), ['record_version' => 2, 'decision' => 'Approved'])->assertUnprocessable();
    expect($course->fresh()->status)->toBe('Published')->and($course->fresh()->publishedRevision->review_status)->toBe('Approved');
    expect(DB::table('notifications')->where('type', 'course.reviewed')->count())->toBe(1);
})->with([Role::Moderator, Role::Administrator]);

test('D06 published course revisions survive edits pending review and rejection', function () {
    $course = courseFixture(true);
    $oldId = $course->published_revision_id;
    $owner = User::find($course->created_by);
    $ids = $course->publishedRevision->modules->pluck('module_id')->all();
    $service = app(CoursePublishing::class);
    $service->save($owner, 'module-test-session', courseData($ids, ['record_version' => 3, 'title' => '<script>New title</script>']), $course);
    $service->submit($owner, 'module-test-session', $course->fresh(), 4);
    expect($course->fresh()->published_revision_id)->toBe($oldId)->and($course->fresh()->title)->toBe('Garden journey');
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 5, 'Rejected', '<script>feedback</script>');
    expect($course->fresh()->published_revision_id)->toBe($oldId);
    moduleSignIn($this, $owner);
    $this->get(route('courses.edit', $course))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>New', false)->assertDontSee('<script>feedback', false);
});

test('D07 approved course snapshots preserve module content and allow reuse across courses', function () {
    $module = moduleFixture(true);
    $first = courseFixture(true, $module);
    $second = courseFixture(true, $module, ['title' => 'Another journey']);
    $oldRevision = $module->published_revision_id;
    $owner = User::find($module->created_by);
    $service = app(ModulePublishing::class);
    $service->save($owner, 'module-test-session', moduleData(['record_version' => 3, 'title' => 'Replacement adventure']), $module);
    $service->submit($owner, 'module-test-session', $module->fresh(), 4);
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 5, 'Approved', null);
    expect($first->publishedRevision->modules[0]->module_revision_id)->toBe($oldRevision)->and($second->publishedRevision->modules[0]->module_revision_id)->toBe($oldRevision);
    moduleSignIn($this, $owner);
    $this->get(route('courses.preview', [$first, $first->latestRevision->modules[0]->id]))->assertOk()->assertSee('Robot picnic')->assertDontSee('Replacement adventure');
    $this->get(route('courses.preview', [$first, $second->latestRevision->modules[0]->id]))->assertNotFound();
});

test('D05 title uniqueness is case insensitive within a category with sanitized conflict errors', function () {
    $course = courseFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->postJson(route('courses.store'), courseData([], ['title' => ' garden JOURNEY ', 'category' => 'programming']))->assertUnprocessable()->assertJsonValidationErrors('title')->assertDontSee('SQLSTATE');
    $this->post(route('courses.store'), courseData([], ['category' => 'Robotics']))->assertRedirect();
    $this->assertDatabaseCount('learning_courses', 2);
});

test('D06 pending edits stale versions and empty publication are rejected', function () {
    $course = courseFixture();
    $owner = User::find($course->created_by);
    moduleSignIn($this, $owner);
    $this->putJson(route('courses.update', $course), courseData([], ['record_version' => 99]))->assertUnprocessable();
    $this->post(route('courses.submit', $course), ['record_version' => 1])->assertRedirect();
    $this->putJson(route('courses.update', $course), courseData([], ['record_version' => 2]))->assertUnprocessable();
    $this->postJson(route('courses.submit', $course), ['record_version' => 2])->assertUnprocessable();
    $empty = app(CoursePublishing::class)->save($owner, session()->getId(), courseData([], ['title' => 'Empty journey']));
    $this->postJson(route('courses.submit', $empty), ['record_version' => 1])->assertUnprocessable();
});

test('G06 course approval rechecks module availability and author status under locks', function () {
    $course = courseFixture();
    $service = app(CoursePublishing::class);
    $owner = User::find($course->created_by);
    $service->submit($owner, 'module-test-session', $course, 1);
    $module = $course->latestRevision->modules[0]->module;
    $module->update(['status' => 'Archived']);
    $reviewer = moduleAccount(Role::Moderator);
    expect(fn () => $service->review($reviewer, 'module-test-session', $course->fresh(), 2, 'Approved', null))->toThrow(ValidationException::class);
    $module->update(['status' => 'Published']);
    $owner->forceFill(['account_status' => AccountStatus::Suspended])->save();
    expect(fn () => $service->review($reviewer, 'module-test-session', $course->fresh(), 2, 'Approved', null))->toThrow(AuthorizationException::class);
    expect($course->fresh()->status)->toBe('Draft');
});

test('D06 renamed course approval cannot collide with another course title', function () {
    $course = courseFixture(true);
    $owner = User::find($course->created_by);
    $service = app(CoursePublishing::class);
    $service->save($owner, 'module-test-session', courseData($course->publishedRevision->modules->pluck('module_id')->all(), ['record_version' => 3, 'title' => 'New journey']), $course);
    $other = $service->save($owner, 'module-test-session', courseData([], ['title' => 'New journey']));
    $service->submit($owner, 'module-test-session', $course->fresh(), 4);
    expect(fn () => $service->review(moduleAccount(Role::Moderator), 'module-test-session', $course->fresh(), 5, 'Approved', null))->toThrow(ValidationException::class);
    expect($course->fresh()->title)->toBe('Garden journey')->and($course->fresh()->latestRevision->review_status)->toBe('Pending');
});

test('D06 audit failure rolls back revision metadata and ordered assignments', function () {
    $course = courseFixture();
    DB::listen(function ($query): void {
        if (str_contains($query->sql, 'insert into "audit_events"')) {
            throw new RuntimeException('Injected course audit failure');
        }
    });
    expect(fn () => app(CoursePublishing::class)->save(User::find($course->created_by), 'module-test-session', courseData([], ['title' => 'Changed journey']), $course))->toThrow(RuntimeException::class);
    expect($course->fresh()->record_version)->toBe(1)->and($course->fresh()->title)->toBe('Garden journey');
    $this->assertDatabaseCount('course_revisions', 1);
    $this->assertDatabaseCount('course_revision_modules', 1);
});

test('PostgreSQL course snapshots reject nonpositive or duplicate positions and mismatched revisions', function () {
    $course = courseFixture();
    $slot = $course->latestRevision->modules[0];
    expect(fn () => DB::transaction(fn () => $slot->update(['position' => 0])))->toThrow(QueryException::class);
    $other = moduleFixture(true);
    expect(fn () => DB::transaction(fn () => $slot->update(['module_revision_id' => $other->published_revision_id])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => CourseRevisionModule::create(['course_revision_id' => $slot->course_revision_id, 'module_id' => $other->id, 'module_revision_id' => $other->published_revision_id, 'position' => 1])))->toThrow(QueryException::class);
});

test('course studio mutations require CSRF independent throttles and authenticated access', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    for ($index = 0; $index < 10; $index++) {
        $this->postJson(route('courses.store'), [])->assertUnprocessable();
    }
    $this->postJson(route('courses.store'), [])->assertTooManyRequests();
    $this->app['env'] = 'production';
    $this->post(route('courses.store'), courseData())->assertStatus(419);
});

test('G06 disallowed course reviewers cannot access review actions or previews', function (Role $role) {
    $course = courseFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('course-reviews.index'))->assertForbidden();
    $this->get(route('course-reviews.show', $course))->assertForbidden();
    $this->postJson(route('course-reviews.review', $course), ['record_version' => 1, 'decision' => 'Approved'])->assertForbidden();
    $this->get(route('courses.preview', [$course, $course->latestRevision->modules[0]->id]))->assertForbidden();
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('D06 course mutations reject stale account roles and replacement sessions', function () {
    $course = courseFixture();
    $actor = User::find($course->created_by);
    User::whereKey($actor->id)->update(['account_role' => Role::Learner]);
    expect(fn () => app(CoursePublishing::class)->save($actor, 'module-test-session', courseData(), $course))->toThrow(AuthorizationException::class);
    User::whereKey($actor->id)->update(['account_role' => Role::Instructor, 'active_session_hash' => hash('sha256', 'replacement-session')]);
    expect(fn () => app(CoursePublishing::class)->submit($actor, 'module-test-session', $course, 1))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('course_revisions', 1);
});

test('F05 course notifications remain private and open the owned course feedback', function () {
    $course = courseFixture(true);
    moduleSignIn($this, User::find($course->created_by));
    $this->get(route('notifications.index'))->assertOk()->assertSee(route('courses.edit', $course))->assertSee('Garden journey');
    moduleSignIn($this, User::factory()->create());
    $this->get(route('notifications.index'))->assertOk()->assertDontSee('Garden journey');
});

test('course editor search bounds input escapes wildcards and preserves saved selections', function () {
    $course = courseFixture();
    moduleSignIn($this, User::find($course->created_by));
    $this->get(route('courses.edit', [$course, 'q' => 'no match']))->assertOk()->assertViewHas('modules', fn ($modules) => $modules->count() === 1);
    $this->get(route('courses.create', ['q' => '%']))->assertOk()->assertViewHas('modules', fn ($modules) => $modules->isEmpty());
    $this->getJson(route('courses.create', ['q' => str_repeat('x', 81)]))->assertUnprocessable();
    $this->get('/create/courses/invalid-id')->assertNotFound();
});

test('D05 removing every picker slot saves an empty private draft and prevents submission', function () {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->post(route('courses.store'), courseData(['']))->assertRedirect();
    $course = LearningCourse::sole();
    expect($course->latestRevision->modules)->toBeEmpty();
    $this->get(route('courses.edit', $course))->assertOk()->assertSee('Save a draft with adventures');
    $this->postJson(route('courses.submit', $course), ['record_version' => 1])->assertUnprocessable();
});
