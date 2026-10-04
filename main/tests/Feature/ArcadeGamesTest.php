<?php

use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Content\ModulePublishing;
use App\Services\Games\ArcadeGames;
use App\Services\Games\GamePresets;
use App\Services\Gamification\LearningProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

dataset('arcade solutions', [
    ['pixel-studio', ['paint 1 1 mint', 'paint 2 2 peach']],
    ['number-machine', ['add 3', 'multiply 2']],
    ['sort-lab', ['swap 1 2', 'swap 2 3']],
    ['terminal-quest', ['ls', 'cat hello.txt', 'cp hello.txt release.txt', 'cat release.txt']],
]);

test('D01 four new templates have deterministic bounded winning programs', function ($template, $program) {
    $engine = app(ArcadeGames::class);
    $instance = $engine->instance($template);
    expect($engine->succeeds($instance, $program))->toBeTrue()
        ->and($engine->succeeds($instance, ['success']))->toBeFalse()
        ->and($engine->succeeds($instance, array_fill(0, 13, $program[0])))->toBeFalse()
        ->and($engine->succeeds(array_replace($instance, ['version' => 2]), $program))->toBeFalse();
})->with('arcade solutions');

test('D01 D02 B05 new games publish render and record only validated wins', function ($template, $program) {
    $module = moduleFixture(true, ['game_preset' => $template]);
    $learner = moduleAccount(Role::Learner);
    moduleSignIn($this, $learner);
    $this->get(route('modules.show', $module))->assertOk()->assertSee('data-arcade-game', false);
    $url = route('modules.game', [$module, $module->published_revision_id]);
    $this->postJson($url, ['program' => ['success'], 'success' => true])->assertUnprocessable();
    expect(DB::table('learning_activity_days')->count())->toBe(0);
    $this->postJson($url, ['program' => $program])->assertOk();
    $this->postJson($url, ['program' => $program])->assertOk();
    expect(DB::table('learning_activity_days')->where('user_id', $learner->id)->count())->toBe(1)
        ->and(DB::table('learning_progress')->where('user_id', $learner->id)->value('current_streak'))->toBe(1);
})->with('arcade solutions');

test('D01 customizable scenarios change the authoritative objectives', function ($template, $scenario, $program) {
    $instance = app(ArcadeGames::class)->instance($template, json_encode($scenario));
    expect($instance['scenario'])->toBe($scenario)->and(app(ArcadeGames::class)->succeeds($instance, $program))->toBeTrue();
})->with([
    ['pixel-studio', ['pixels' => ['3 1 lavender']], ['paint 3 1 lavender']],
    ['number-machine', ['start' => -8, 'target' => 20], ['add 28']],
    ['sort-lab', ['items' => [9, 4, 4]], ['swap 1 3']],
    ['terminal-quest', ['files' => [['name' => 'lesson.txt', 'content' => '<script>example</script>']], 'destination' => 'answer.txt', 'content' => '<script>example</script>'], ['cp lesson.txt answer.txt', 'cat answer.txt']],
]);

test('D01 malformed unreachable or executable scenario payloads roll back drafts', function ($template, $scenario) {
    $author = moduleAccount(Role::Instructor);
    expect(fn () => app(ModulePublishing::class)->save($author, 'module-test-session', moduleData(['game_preset' => $template, 'game_scenario' => $scenario])))
        ->toThrow(ValidationException::class);
    expect(LearningModule::count())->toBe(0)->and(DB::table('audit_events')->count())->toBe(0);
})->with([
    ['pixel-studio', 'null'], ['pixel-studio', '[]'], ['pixel-studio', 'not json'],
    ['pixel-studio', '{"pixels":["1 1 mint","1 1 peach"]}'], ['pixel-studio', '{"pixels":["4 1 mint"]}'],
    ['pixel-studio', '{"pixels":["1 1 mint"],"source":"alert(1)"}'],
    ['number-machine', '{"start":"2","target":10}'], ['number-machine', '{"start":2.5,"target":10}'],
    ['number-machine', '{"start":2,"target":100000}'], ['sort-lab', '{"items":[1]}'],
    ['sort-lab', '{"items":[2,"1"]}'],
    ['terminal-quest', '{"files":[{"name":"../key.txt","content":"x"}],"destination":"done.txt","content":"x"}'],
    ['terminal-quest', '{"files":[{"name":"ok.txt","content":"x"}],"destination":"done.txt","content":"missing"}'],
    ['terminal-quest', '{"files":[{"name":"ok.txt","content":"x"}],"destination":"ok.txt","content":"x"}'],
]);

test('D02 changed arcade drafts preserve published objectives and require review', function () {
    $module = moduleFixture(true, ['game_preset' => 'number-machine']);
    $published = $module->publishedRevision;
    $service = app(ModulePublishing::class);
    $service->save(User::findOrFail($module->created_by), 'module-test-session', moduleData(['record_version' => 3, 'game_preset' => 'number-machine',
        'game_scenario' => '{"start":1,"target":9}']), $module);
    expect($module->fresh()->published_revision_id)->toBe($published->id)->and($published->fresh()->assessment['scenario']['target'])->toBe(10);
    $learner = moduleAccount(Role::Learner);
    expect(fn () => app(LearningProgression::class)->recordModule($learner, 'module-test-session', $module->id, $module->fresh()->latestRevision->id, 'game', ['program' => ['add 8']]))
        ->toThrow(HttpException::class);
});

test('G08 new managed templates pin custom scenarios into creator lessons', function ($template, $program) {
    $admin = moduleAccount(Role::Administrator);
    $preset = app(GamePresets::class)->save($admin, 'module-test-session', ['name' => 'Reusable '.$template, 'record_version' => 1,
        'basis' => $template, 'title' => 'Custom mission', 'instructions' => 'Complete the objective.', 'hint' => 'Try the example.',
        'learning_idea' => 'You changed the state.', 'reward_mode' => 'Deferred']);
    $module = moduleFixture(true, ['assessment_kind' => 'preset', 'managed_preset' => $preset->current_revision_id, 'preset_title' => 'Creator mission']);
    expect($module->publishedRevision->assessment['template'])->toBe($template)
        ->and(app(ArcadeGames::class)->succeeds($module->publishedRevision->assessment, $program))->toBeTrue();
})->with('arcade solutions');

test('game-first guests can try four games without completion endpoints', function ($template, $program) {
    $this->get(route('arcade', ['game' => $template]))->assertOk()->assertSee(config('arcade')[$template]['title'])
        ->assertSee('data-arcade-game', false)->assertDontSee('data-completion-url', false);
    expect(DB::table('learning_activity_days')->count())->toBe(0);
})->with('arcade solutions');

test('B04 new templates retain pinned course objectives and enrollment authorization', function () {
    $module = moduleFixture(true, ['game_preset' => 'number-machine']);
    $course = courseFixture(true, $module);
    $slot = $course->publishedRevision->modules->sole();
    $learner = moduleAccount(Role::Learner);
    moduleSignIn($this, $learner);
    $url = route('course-learning.game', [$course, $slot->id]);
    $this->postJson($url, ['program' => ['add 8']])->assertForbidden();
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect();
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk()->assertSee('data-arcade-game', false);
    $owner = User::findOrFail($module->created_by);
    $service = app(ModulePublishing::class);
    $service->save($owner, 'module-test-session', moduleData(['record_version' => 3, 'game_preset' => 'number-machine', 'game_scenario' => '{"start":1,"target":9}']), $module);
    $service->submit($owner, 'module-test-session', $module->fresh(), 4);
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 5, 'Approved', null);
    $this->postJson($url, ['program' => ['add 7']])->assertUnprocessable();
    $this->postJson($url, ['program' => ['add 8']])->assertOk();
    expect(DB::table('course_module_progress')->where('assignment_id', $slot->id)->value('completed_at'))->not->toBeNull();
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->postJson($url, ['program' => ['add 8']])->assertNotFound();
});

test('G08 arcade presets retain custom scenarios and safely refuse incompatible rollback', function () {
    $preset = presetFixture(['basis' => 'number-machine', 'game_scenario' => '{"start":5,"target":7}']);
    $revision = $preset->currentRevision;
    expect($revision->instance['scenario'])->toBe(['start' => 5, 'target' => 7]);
    $migration = require database_path('migrations/2026_10_04_000028_expand_game_preset_templates.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    $preset->refresh();
    expect($preset->current_revision_id)->toBe($revision->id);
});
