<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Content\ModulePublishing;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function moduleData(array $overrides = []): array
{
    return array_replace(['record_version' => 1, 'title' => 'Robot picnic', 'description' => 'Teach a robot to find its picnic.',
        'content' => "Instructions run in order.\nright();", 'type' => 'Interactive', 'assessment_kind' => 'game',
        'game_preset' => 'sequences', 'game_title' => 'Picnic trail', 'game_instructions' => 'Guide the robot to its picnic.',
        'game_hint' => 'Right, right, up, right, right.', 'game_learning_idea' => 'Order changes the destination.',
        'quiz_title' => 'Picnic idea', 'quiz_question' => 'Do instructions run in order?', 'quiz_a' => 'Yes', 'quiz_b' => 'No',
        'quiz_answer' => 'a', 'quiz_explanation' => 'A sequence runs in order.'], $overrides);
}

function moduleAccount(Role $role): User
{
    return User::factory()->create(['account_role' => $role, 'active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()]);
}

function moduleFixture(bool $published = false, array $data = []): LearningModule
{
    $author = moduleAccount(Role::Instructor);
    $service = app(ModulePublishing::class);
    $module = $service->save($author, 'module-test-session', moduleData($data));
    if ($published) {
        $service->submit($author, 'module-test-session', $module, 1);
        $reviewer = moduleAccount(Role::Moderator);
        $service->review($reviewer, 'module-test-session', $module->fresh(), 2, 'Approved', null);
    }

    return $module->fresh();
}

function moduleSignIn($test, User $user): void
{
    if (auth()->check()) {
        $test->post(route('logout'))->assertRedirect(route('login'));
    }
    $user->forceFill(['active_session_hash' => null, 'active_session_expires_at' => null])->save();
    $test->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $test->withCredentials()->withCookie(config('session.cookie'), session()->getId());
}

test('D01 instructors create owned drafts with allowlisted template data and escaped previews', function () {
    $author = User::factory()->create(['account_role' => Role::Instructor]);
    moduleSignIn($this, $author);
    $this->get(route('studio.create'))->assertOk()->assertSee('Start with a spark');
    $this->post(route('studio.store'), moduleData(['title' => '<script>bad()</script>', 'created_by' => 999,
        'status' => 'Published', 'published_revision_id' => 999, 'assessment' => ['source' => 'bad()']]))->assertRedirect();
    $module = LearningModule::sole();
    expect($module->created_by)->toBe($author->id)->and($module->status)->toBe('Draft')->and($module->published_revision_id)->toBeNull();
    expect($module->latestRevision->assessment)->toHaveKey('path')->not->toHaveKey('source');
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>bad()', false)->assertSee('data-coding-game', false)->assertDontSee('data-completion-url', false)->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('studio.index'))->assertOk();
    $this->get(route('modules.show', $module))->assertNotFound();
});

test('D01 other roles cannot create or inspect instructor drafts', function (Role $role) {
    $module = moduleFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('studio.index'))->assertForbidden();
    $this->get(route('studio.edit', $module))->assertForbidden();
    $this->postJson(route('studio.store'), moduleData())->assertForbidden();
    $this->assertDatabaseCount('learning_modules', 1);
})->with([Role::Learner, Role::Contributor, Role::Moderator, Role::Administrator]);

test('D02 ownership applies to edit and submit even with a forged owner', function () {
    $module = moduleFixture();
    $other = User::factory()->create(['account_role' => Role::Instructor]);
    moduleSignIn($this, $other);
    $this->putJson(route('studio.update', $module), moduleData(['created_by' => $other->id]))->assertForbidden();
    $this->postJson(route('studio.submit', $module), ['record_version' => 1])->assertForbidden();
    $this->assertDatabaseCount('module_revisions', 1);
});

test('D01 validates content formats bounded fields and typed assessment placeholders', function (array $overrides, string $field) {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->postJson(route('studio.store'), moduleData($overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('learning_modules', 0);
})->with([
    [['title' => str_repeat('x', 151)], 'title'], [['content' => ''], 'content'], [['type' => 'Executable'], 'type'],
    [['assessment_kind' => 'none'], 'assessment_kind'], [['game_preset' => '../bad'], 'game_preset'],
    [['game_instructions' => str_repeat('x', 1001)], 'game_instructions'],
    [['assessment_kind' => 'quiz', 'quiz_answer' => 'admin'], 'quiz_answer'],
    [['assessment_kind' => 'quiz', 'quiz_a' => 'No'], 'quiz_a'],
    [['type' => 'Video', 'video_url' => 'javascript:bad()'], 'video_url'],
]);

test('D01 video links are HTTPS and code examples stay text', function () {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Instructor]));
    $this->post(route('studio.store'), moduleData(['type' => 'Video', 'video_url' => 'https://example.test/video', 'assessment_kind' => 'none', 'content' => '<iframe src="bad"></iframe>']))->assertRedirect();
    $module = LearningModule::sole();
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('https://example.test/video')->assertSee('&lt;iframe', false)->assertDontSee('<iframe src="bad">', false);
});

test('D02 pending revisions cannot be edited resubmitted or exposed', function () {
    $module = moduleFixture();
    moduleSignIn($this, User::find($module->created_by));
    $this->post(route('studio.submit', $module), ['record_version' => 1])->assertRedirect();
    $this->postJson(route('studio.submit', $module), ['record_version' => 1])->assertUnprocessable();
    $this->putJson(route('studio.update', $module), moduleData(['record_version' => 2]))->assertUnprocessable();
    $this->get(route('learning.catalog'))->assertDontSee('Robot picnic');
    $this->get(route('modules.show', $module))->assertNotFound();
    $this->assertDatabaseCount('module_revisions', 1);
});

test('G06 reviewers approve the exact pending revision and cannot review twice', function (Role $role) {
    $module = moduleFixture();
    app(ModulePublishing::class)->submit(User::find($module->created_by), 'module-test-session', $module, 1);
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('module-reviews.index'))->assertOk()->assertSee('Robot picnic');
    $this->get(route('module-reviews.show', $module))->assertOk()->assertSee('data-coding-game', false)->assertDontSee('data-completion-url', false);
    $this->post(route('module-reviews.review', $module), ['record_version' => 2, 'decision' => 'Approved'])->assertRedirect();
    $this->postJson(route('module-reviews.review', $module), ['record_version' => 2, 'decision' => 'Rejected', 'review_notes' => 'Late'])->assertUnprocessable();
    expect($module->fresh()->status)->toBe('Published')->and($module->fresh()->publishedRevision->review_status)->toBe('Approved');
    $this->assertDatabaseCount('audit_events', 3);
    $this->get(route('learning.catalog', ['q' => 'PICNIC']))->assertOk()->assertSee('Robot picnic');
    $this->get(route('modules.show', $module))->assertOk()->assertSee('Staff preview')->assertDontSee('data-completion-url', false);
})->with([Role::Moderator, Role::Administrator]);

test('G06 author learner and contributor cannot review modules', function (Role $role) {
    $module = moduleFixture();
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('module-reviews.index'))->assertForbidden();
    $this->get(route('module-reviews.show', $module))->assertForbidden();
    $this->postJson(route('module-reviews.review', $module), ['record_version' => 1, 'decision' => 'Approved'])->assertForbidden();
})->with([Role::Instructor, Role::Learner, Role::Contributor]);

test('D02 live revisions survive drafts pending changes and rejected replacements', function () {
    $module = moduleFixture(true);
    $publishedId = $module->published_revision_id;
    $author = User::find($module->created_by);
    $service = app(ModulePublishing::class);
    $service->save($author, 'module-test-session', moduleData(['record_version' => 3, 'title' => 'Secret new title']), $module);
    $service->submit($author, 'module-test-session', $module->fresh(), 4);
    moduleSignIn($this, User::factory()->create());
    $this->get(route('modules.show', $module))->assertSee('Robot picnic')->assertDontSee('Secret new title');
    $reviewer = moduleAccount(Role::Moderator);
    $service->review($reviewer, 'module-test-session', $module->fresh(), 5, 'Rejected', '<script>feedback</script>');
    expect($module->fresh()->published_revision_id)->toBe($publishedId);
    $service->save($author, 'module-test-session', moduleData(['record_version' => 6, 'title' => 'Approved new title']), $module->fresh());
    $service->submit($author, 'module-test-session', $module->fresh(), 7);
    $service->review($reviewer, 'module-test-session', $module->fresh(), 8, 'Approved', null);
    $this->get(route('modules.show', $module))->assertSee('Approved new title')->assertDontSee('Robot picnic');
    $this->get(route('learning.catalog', ['q' => 'Robot picnic']))->assertDontSee('Robot picnic</h2>', false);
    expect(ModuleRevision::find($publishedId)->title)->toBe('Robot picnic');
});

test('D02 fresh authorization rejects revoked roles and superseded sessions', function () {
    $module = moduleFixture();
    $actor = User::find($module->created_by);
    User::whereKey($actor->id)->update(['account_role' => Role::Learner]);
    expect(fn () => app(ModulePublishing::class)->save($actor, 'module-test-session', moduleData(), $module))->toThrow(AuthorizationException::class);
    User::whereKey($actor->id)->update(['account_role' => Role::Instructor, 'active_session_hash' => hash('sha256', 'new-session')]);
    expect(fn () => app(ModulePublishing::class)->submit($actor, 'module-test-session', $module, 1))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('module_revisions', 1);
});

test('G06 suspended authors cannot have pending modules approved', function () {
    $module = moduleFixture();
    $author = User::find($module->created_by);
    app(ModulePublishing::class)->submit($author, 'module-test-session', $module, 1);
    $author->forceFill(['account_status' => AccountStatus::Suspended])->save();
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Moderator]));
    $this->postJson(route('module-reviews.review', $module), ['record_version' => 2, 'decision' => 'Approved'])->assertUnprocessable();
    expect($module->fresh()->status)->toBe('Draft');
    $this->postJson(route('module-reviews.review', $module), ['record_version' => 2, 'decision' => 'Rejected', 'review_notes' => ''])->assertUnprocessable();
    $this->post(route('module-reviews.review', $module), ['record_version' => 2, 'decision' => 'Rejected', 'review_notes' => 'Revise this lesson'])->assertRedirect();
});

test('published games require server replay and save only one daily activity without skipping the ladder', function () {
    $module = moduleFixture(true);
    moduleSignIn($this, User::factory()->create());
    $url = route('modules.game', [$module, $module->published_revision_id]);
    $this->postJson($url, ['program' => ['left'], 'success' => true])->assertUnprocessable();
    $this->postJson($url, ['program' => ['right', 'right', 'up', 'right', 'right'], 'streak' => 900])->assertOk()->assertJsonPath('progress.current_streak', 1);
    $this->postJson($url, ['program' => ['right', 'right', 'up', 'right', 'right']])->assertOk();
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_level_completions', 0);
    $this->postJson(route('modules.quiz', [$module, $module->published_revision_id]), ['answer' => 'a'])->assertNotFound();
    $this->postJson(route('modules.game', [$module, $module->published_revision_id + 999]), ['program' => ['right']])->assertStatus(409);
});

test('published creator quiz uses the stored answer and rejects stale or unpublished attempts', function () {
    $module = moduleFixture(true, ['assessment_kind' => 'quiz']);
    moduleSignIn($this, User::factory()->create());
    $this->get(route('modules.show', $module))->assertOk()->assertSee('data-practice-quiz', false);
    $url = route('modules.quiz', [$module, $module->published_revision_id]);
    $this->postJson($url, ['answer' => 'b', 'success' => true])->assertUnprocessable();
    $this->postJson($url, ['answer' => 'a'])->assertOk()->assertJsonPath('progress.current_streak', 1);
    $this->postJson($url, ['answer' => 'a'])->assertOk();
    $module->update(['status' => 'Archived']);
    $this->get(route('modules.show', $module))->assertNotFound();
    $this->postJson($url, ['answer' => 'a'])->assertStatus(409);
    $this->assertDatabaseCount('learning_activity_days', 1);
});

test('guests see published metadata and must sign in to read creator modules', function () {
    $published = moduleFixture(true);
    moduleFixture(false, ['title' => 'Private unpublished lesson']);
    $this->get(route('learning.catalog'))->assertOk()->assertSee('Robot picnic')->assertDontSee('Private unpublished lesson');
    $this->get(route('modules.show', $published))->assertRedirect(route('login'));
    $this->get(route('studio.index'))->assertRedirect(route('login'));
});

test('D02 stale writes and audit failure roll back the entire new revision', function () {
    $module = moduleFixture();
    $actor = User::find($module->created_by);
    $service = app(ModulePublishing::class);
    $service->save($actor, 'module-test-session', moduleData(), $module);
    expect(fn () => $service->save($actor, 'module-test-session', moduleData(), $module))->toThrow(ValidationException::class);
    DB::listen(function ($query): void {
        if (str_contains($query->sql, 'insert into "audit_events"')) {
            throw new RuntimeException('Injected audit failure');
        }
    });
    expect(fn () => $service->save($actor, 'module-test-session', moduleData(['record_version' => 2]), $module))->toThrow(RuntimeException::class);
    expect($module->fresh()->record_version)->toBe(2);
    $this->assertDatabaseCount('module_revisions', 2);
    $this->assertDatabaseCount('audit_events', 2);
});

test('PostgreSQL rejects cross-module publication pointers', function () {
    $one = moduleFixture();
    $two = moduleFixture();
    expect(fn () => DB::table('learning_modules')->where('id', $one->id)->update(['published_revision_id' => $two->latestRevision->id, 'status' => 'Published']))->toThrow(QueryException::class);
});

test('D03 owner confirms archive and learner access stops while history survives', function () {
    $module = moduleFixture(true);
    $learner = moduleAccount(Role::Learner);
    app(LearningProgression::class)->recordModule($learner, 'module-test-session', $module->id, $module->published_revision_id, 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]);
    moduleSignIn($this, User::find($module->created_by));
    $this->get(route('studio.archive-confirmation', $module))->assertOk()->assertSee('Confirm archive');
    $this->postJson(route('studio.archive', $module), ['record_version' => 3])->assertUnprocessable();
    $this->postJson(route('studio.archive', $module), ['record_version' => 2, 'confirmed' => true])->assertUnprocessable();
    $this->post(route('studio.archive', $module), ['record_version' => 3, 'confirmed' => true])->assertRedirect();
    expect($module->fresh()->status)->toBe('Archived');
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('Archived');
    $this->get(route('modules.show', $module))->assertNotFound();
    $this->get(route('learning.catalog'))->assertDontSee('Robot picnic');
    $this->putJson(route('studio.update', $module), moduleData(['record_version' => 4]))->assertForbidden();
    $this->postJson(route('studio.archive', $module), ['record_version' => 4, 'confirmed' => true])->assertForbidden();
    $this->assertDatabaseCount('module_revisions', 1);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseHas('audit_events', ['event' => 'module.archived']);
});

test('D03 only the owning Instructor can archive a Published module', function () {
    $module = moduleFixture();
    moduleSignIn($this, User::find($module->created_by));
    $this->get(route('studio.archive-confirmation', $module))->assertForbidden();
    $this->postJson(route('studio.archive', $module), ['record_version' => 1, 'confirmed' => true])->assertForbidden();
    $other = moduleFixture(true);
    $this->postJson(route('studio.archive', $other), ['record_version' => 3, 'confirmed' => true])->assertForbidden();
});

test('studio and review mutations require CSRF with independent throttles', function () {
    $module = moduleFixture();
    moduleSignIn($this, User::find($module->created_by));
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('studio.store'), [])->assertUnprocessable();
    }
    $this->postJson(route('studio.store'), [])->assertTooManyRequests();
    $this->putJson(route('studio.update', $module), [])->assertUnprocessable();
    $this->app['env'] = 'production';
    $this->post(route('studio.submit', $module), ['record_version' => 1])->assertStatus(419);
    $this->post(route('studio.archive', $module), ['record_version' => 1, 'confirmed' => true])->assertStatus(419);
});

test('F05 review updates stay private escape creator text and mark read idempotently', function () {
    $module = moduleFixture(true, ['title' => '<script>creator text</script>']);
    $notification = DB::table('notifications')->sole();
    moduleSignIn($this, User::factory()->create());
    $this->get(route('notifications.index'))->assertOk()->assertDontSee('creator text');
    $this->post(route('notifications.read', $notification->id))->assertNotFound();
    moduleSignIn($this, User::find($module->created_by));
    $this->get(route('notifications.index'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>creator text', false)->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('notifications.read', $notification->id))->assertRedirect();
    $this->post(route('notifications.read', $notification->id))->assertRedirect();
    expect(DB::table('notifications')->value('read_at'))->not->toBeNull();
    $this->assertDatabaseCount('notifications', 1);
});

test('G06 moderation failure rolls back the decision publication audit and notification', function () {
    $module = moduleFixture();
    $service = app(ModulePublishing::class);
    $service->submit(User::find($module->created_by), 'module-test-session', $module, 1);
    $reviewer = moduleAccount(Role::Moderator);
    DB::listen(function ($query): void {
        if (str_contains($query->sql, 'insert into "notifications"')) {
            throw new RuntimeException('Injected inbox failure');
        }
    });
    expect(fn () => $service->review($reviewer, 'module-test-session', $module, 2, 'Approved', null))->toThrow(RuntimeException::class);
    expect($module->fresh()->status)->toBe('Draft')->and($module->fresh()->latestRevision->review_status)->toBe('Pending');
    $this->assertDatabaseCount('audit_events', 2);
    $this->assertDatabaseCount('notifications', 0);
});

test('learning search treats percent and underscore as text and paginates creator modules', function () {
    moduleFixture(true);
    $this->get(route('learning.catalog', ['q' => '%']))->assertOk()->assertDontSee('Robot picnic');
    $this->get(route('learning.catalog', ['q' => '_']))->assertOk()->assertDontSee('Robot picnic');
    for ($index = 0; $index < 12; $index++) {
        moduleFixture(true, ['title' => 'Adventure '.$index]);
    }
    $this->get(route('learning.catalog'))->assertOk()->assertViewHas('published', fn ($published) => $published->count() === 12 && $published->total() === 13);
});
