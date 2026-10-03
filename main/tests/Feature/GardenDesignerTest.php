<?php

use App\Enums\Role;
use App\Models\LearningModule;
use App\Services\Content\ModulePublishing;
use App\Services\Games\CommandGarden;
use App\Services\Games\GardenLayout;
use App\Services\Gamification\LearningProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function customGardenLayout(array $changes = []): string
{
    return json_encode(array_replace(['start' => [0, 0], 'goal' => [4, 0],
        'path' => [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]], 'crystals' => []], $changes), JSON_THROW_ON_ERROR);
}

test('D01 game layouts preserve the built-in defaults when omitted', function (string $preset) {
    $instance = app(GardenLayout::class)->instance(['game_preset' => $preset]);
    expect($instance)->toBe(config('learning.instances')[$preset]);
    $solution = app(GardenLayout::class)->solution($instance);
    expect($solution)->not->toBeNull()->and(app(CommandGarden::class)->succeeds($instance, $solution, true, true))->toBeTrue();
})->with(['sequences', 'loops', 'conditions']);

test('D01 custom garden worlds are bounded solvable template data', function (string $preset) {
    $data = ['game_preset' => $preset, 'game_layout' => customGardenLayout($preset === 'conditions' ? ['crystals' => [[2, 0], [4, 0]]] : [])];
    $instance = app(GardenLayout::class)->instance($data);
    $solution = app(GardenLayout::class)->solution($instance);
    expect($instance['goal'])->toBe([4, 0])->and($instance['start'])->toBe([0, 0])->and($instance['version'])->toBe(1)
        ->and($instance['template'])->toBe('command-garden')->and($solution)->not->toBeNull()
        ->and(app(CommandGarden::class)->succeeds($instance, $solution, true, true))->toBeTrue();
})->with(['sequences', 'loops', 'conditions']);

test('D01 malformed unsafe and impossible garden data are rejected without writes', function (string $layout, string $preset) {
    $author = moduleAccount(Role::Instructor);
    expect(fn () => app(ModulePublishing::class)->save($author, 'module-test-session', moduleData(['game_layout' => $layout, 'game_preset' => $preset])))
        ->toThrow(ValidationException::class);
    expect(LearningModule::count())->toBe(0)->and(DB::table('module_revisions')->count())->toBe(0)->and(DB::table('audit_events')->count())->toBe(0);
})->with([
    ['not json', 'sequences'], ['null', 'sequences'], [str_repeat('x', 4001), 'sequences'],
    [customGardenLayout(['source' => 'alert(1)']), 'sequences'], [customGardenLayout(['width' => 999]), 'sequences'],
    [customGardenLayout(['start' => [0, 0, 0]]), 'sequences'], [customGardenLayout(['start' => ['0', 0]]), 'sequences'],
    [customGardenLayout(['goal' => [5, 0]]), 'sequences'], [customGardenLayout(['goal' => [0, 0]]), 'sequences'],
    [customGardenLayout(['path' => [[0, 0], [0, 0], [4, 0]]]), 'sequences'],
    [customGardenLayout(['path' => [[0, 0], [4, 0]]]), 'sequences'],
    [customGardenLayout(['crystals' => [[1, 0]]]), 'sequences'],
    [customGardenLayout(['crystals' => [[0, 0]]]), 'conditions'],
    [customGardenLayout(['crystals' => [[0, 1]]]), 'conditions'],
    [customGardenLayout(['crystals' => [[1, 0], [1, 0]]]), 'conditions'],
    [customGardenLayout(['goal' => [3, 0]]), 'loops'],
]);

test('D01 D02 garden designer saves custom worlds with live preview and preserves publication during edits', function () {
    $author = moduleAccount(Role::Instructor);
    moduleSignIn($this, $author);
    $this->get(route('studio.create'))->assertOk()->assertSee('Build a little world')->assertSee('data-garden-designer', false)
        ->assertSee('data-designer-preview-host', false)->assertDontSee('data-completion-url', false);
    $this->post(route('studio.store'), moduleData(['game_layout' => customGardenLayout()]))->assertRedirect();
    $module = LearningModule::sole();
    $saved = $module->latestRevision;
    expect($saved->assessment['start'])->toBe([0, 0])->and($saved->assessment['goal'])->toBe([4, 0]);
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('Start: column 1, row 1. Goal: column 5, row 1.')
        ->assertDontSee('data-completion-url', false);
    $service = app(ModulePublishing::class);
    $service->submit($author, session()->getId(), $module, 1);
    $reviewer = moduleAccount(Role::Moderator);
    $service->review($reviewer, 'module-test-session', $module->fresh(), 2, 'Approved', null);
    $approved = $module->fresh()->published_revision_id;
    $this->put(route('studio.update', $module), moduleData(['record_version' => 3,
        'game_layout' => customGardenLayout(['goal' => [2, 0]])]))->assertRedirect();
    expect($module->fresh()->published_revision_id)->toBe($approved)
        ->and($saved->fresh()->assessment['goal'])->toBe([4, 0])->and($module->fresh()->latestRevision->assessment['goal'])->toBe([2, 0]);
});

test('B05 custom published objectives require authoritative completion and retain daily idempotency', function () {
    $module = moduleFixture(true, ['game_layout' => customGardenLayout()]);
    $learner = moduleAccount(Role::Learner);
    $service = app(LearningProgression::class);
    expect(fn () => $service->recordModule($learner, 'module-test-session', $module->id, $module->published_revision_id, 'game',
        ['program' => ['right'], 'success' => true]))->toThrow(ValidationException::class);
    $input = ['program' => ['right', 'right', 'right', 'right']];
    $service->recordModule($learner, 'module-test-session', $module->id, $module->published_revision_id, 'game', $input);
    $service->recordModule($learner, 'module-test-session', $module->id, $module->published_revision_id, 'game', $input);
    expect(DB::table('learning_activity_days')->where('user_id', $learner->id)->count())->toBe(1)
        ->and(DB::table('learning_progress')->where('user_id', $learner->id)->value('current_streak'))->toBe(1);
});

test('D01 garden layout input is excluded when using another assessment kind', function () {
    $author = moduleAccount(Role::Instructor);
    moduleSignIn($this, $author);
    $this->post(route('studio.store'), moduleData(['assessment_kind' => 'quiz', 'game_layout' => 'invalid JSON']))->assertRedirect();
    expect(LearningModule::sole()->latestRevision->assessment['template'])->toBe('choice-quiz');
});
