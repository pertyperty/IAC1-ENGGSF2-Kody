<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Content\CourseLearning;
use App\Services\Content\LearningHistory;
use App\Services\Content\ModulePublishing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('saved catalogs reflect course reading and assessment completions without changing play credit', function () {
    [$course, $reading, $game] = pathFixture();
    $user = moduleAccount(Role::Learner);
    $enrollment = joinCourse($course, $user);
    [$readSlot, $gameSlot] = $course->publishedRevision->modules->all();
    moduleSignIn($this, $user);
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Not started')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('course-learning.lesson', [$course, $readSlot->id]))->assertOk();
    $this->post(route('course-learning.read', [$course, $readSlot->id]), ['confirmed' => true])->assertRedirect();
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Completed · read in course');
    $this->get(route('course-learning.catalog'))->assertOk()->assertSee('1 / 2 lessons')->assertSee('50%');
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Last activity')->assertSee('Continue lesson')->assertSee(route('course-learning.lesson', [$course, $gameSlot->id]));
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('xp_awards', 1);
    expect((int) DB::table('xp_totals')->where('user_id', $user->id)->value('xp'))->toBe(40);
    $this->postJson(route('course-learning.game', [$course, $gameSlot->id]), courseWin())->assertOk();
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Completed · assessment cleared');
    $this->get(route('course-learning.catalog'))->assertOk()->assertSee('2 / 2 lessons')->assertSee('100%');
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Completed')->assertSee('Recent modules');
    $summary = app(CourseLearning::class)->summaries($user, collect([$enrollment]));
    expect($summary[$enrollment->id]['completed_at'])->not->toBeNull();
});

test('progress histories are owned private and read-only including revoked and withdrawn continuations', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    $enrollment = joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id);
    app(CourseLearning::class)->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin());
    moduleSignIn($this, User::factory()->create());
    $this->get(route('learning.catalog'))->assertOk()->assertDontSee('Completed · assessment cleared');
    $this->get(route('course-learning.catalog'))->assertOk()->assertSee('Not joined')->assertDontSee('1 / 1 lessons');
    $this->get(route('course-learning.mine'))->assertOk()->assertDontSee('Garden journey');
    moduleSignIn($this, $user);
    DB::table('content_entitlements')->where('user_id', $user->id)->where('content_type', 'course')->update(['revoked_at' => now()]);
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Review access')->assertDontSee('Continue lesson');
    expect(DB::table('content_entitlements')->where('user_id', $user->id)->whereNotNull('revoked_at')->count())->toBe(1);
    $course->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Journey temporarily unavailable')->assertDontSee('Garden journey')->assertDontSee(route('course-learning.show', $course));
    expect(fn () => app(CourseLearning::class)->summaries(User::factory()->create(), collect([$enrollment])))->toThrow(HttpException::class);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->get(route('learning.catalog'))->assertOk()->assertDontSee('Completed · assessment cleared');
});

test('catalog retains earlier edition completion without claiming the new edition was cleared', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin());
    $module = $slot->module;
    $owner = User::findOrFail($module->created_by);
    $service = app(ModulePublishing::class);
    $service->save($owner, 'module-test-session', moduleData(['record_version' => 3, 'title' => 'New lesson edition']), $module);
    $service->submit($owner, 'module-test-session', $module->fresh(), 4);
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 5, 'Approved', null);
    moduleSignIn($this, $user);
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Completed · earlier edition')->assertDontSee('Completed · assessment cleared');
    $before = DB::table('xp_awards')->count();
    $history = app(LearningHistory::class)->modules($user, collect([$module->fresh()->publishedRevision]));
    expect($history[$module->id]['state'])->toBe('complete')->and(DB::table('xp_awards')->count())->toBe($before);
});

test('standalone assessment wins are reflected without course enrollment or browser assertions', function () {
    $module = moduleFixture(true);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $this->get(route('learning.catalog'))->assertOk()->assertDontSee('Completed · assessment cleared');
    $this->postJson(route('modules.game', [$module, $module->published_revision_id]), courseWin())->assertOk();
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Completed · assessment cleared');
    $this->assertDatabaseCount('course_enrollments', 0);
    $this->assertDatabaseCount('course_module_progress', 0);
    $this->postJson(route('play.game', 'sequences'), courseWin())->assertOk();
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Completed · game cleared');
});
