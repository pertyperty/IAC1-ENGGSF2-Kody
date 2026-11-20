<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('bookmarks are owned idempotent reminders without enrollment or rewards', function () {
    $module = moduleFixture(true);
    $course = courseFixture(true);
    $learner = moduleAccount(Role::Learner);
    moduleSignIn($this, $learner);
    foreach (['module' => $module, 'course' => $course] as $kind => $content) {
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('bookmarks.update', [$kind, $content->id]), ['action' => 'add', 'user_id' => 999])->assertRedirect();
        }
    }
    $this->assertDatabaseCount('learning_bookmarks', 2);
    $this->assertDatabaseCount('course_enrollments', 0);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->get(route('course-learning.mine', ['type' => 'bookmarks']))->assertOk()->assertSee($module->publishedRevision->title)->assertSee($course->publishedRevision->title);
    $this->get(route('course-learning.mine', ['type' => 'bookmarks', 'q' => '%']))->assertOk()->assertDontSee($module->publishedRevision->title)->assertDontSee($course->publishedRevision->title);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('course-learning.mine', ['type' => 'bookmarks']))->assertOk()->assertDontSee($module->publishedRevision->title);
    $this->post(route('bookmarks.update', ['module', $module->id]), ['action' => 'remove'])->assertRedirect();
    $this->assertDatabaseCount('learning_bookmarks', 2);
    expect(DB::table('learning_bookmarks')->where('user_id', $learner->id)->count())->toBe(2);
});

test('private and withdrawn content cannot be bookmarked and unavailable bookmarks can be removed', function () {
    $draft = moduleFixture();
    $module = moduleFixture(true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->post(route('bookmarks.update', ['module', $draft->id]), ['action' => 'add'])->assertNotFound();
    $this->post(route('bookmarks.update', ['module', $module->id]), ['action' => 'add'])->assertRedirect();
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->post(route('bookmarks.update', ['module', $module->id]), ['action' => 'add'])->assertNotFound();
    $this->get(route('course-learning.mine', ['type' => 'bookmarks']))->assertOk()->assertSee('Module unavailable')->assertDontSee($module->publishedRevision->title);
    $this->post(route('bookmarks.update', ['module', $module->id]), ['action' => 'remove'])->assertRedirect();
    $this->assertDatabaseCount('learning_bookmarks', 0);
    $this->postJson(route('bookmarks.update', ['module', $draft->id]), ['action' => 'invent'])->assertUnprocessable();
    $this->getJson(route('course-learning.mine', ['type' => 'invent']))->assertUnprocessable();
});

test('Help embeds the FAQ editor only for administrators', function () {
    $entry = faqFixture(['answer' => '<script>plain text</script>']);
    $this->get(route('help.index', ['edit' => $entry->id]))->assertOk()->assertDontSee('data-live-preview', false)->assertDontSee('Save to Help');
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('help.index', ['edit' => $entry->id]))->assertOk()->assertDontSee('Save to Help');
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->get(route('help.index', ['edit' => $entry->id]))->assertOk()->assertSee('Save to Help')->assertSee('data-live-preview', false)->assertSee('&lt;script&gt;plain text&lt;/script&gt;', false)->assertDontSee('<script>plain text</script>', false);
});
