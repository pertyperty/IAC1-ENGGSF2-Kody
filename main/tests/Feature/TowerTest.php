<?php

use App\Enums\Role;
use App\Models\TowerLevel;
use App\Services\Account\AccountDeletion;
use App\Services\Games\GardenLayout;
use App\Services\Games\TowerAuthoring;
use App\Services\Gamification\TowerProgression;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function towerCircuitSolution(array $scenario): array
{
    $desired = array_fill(0, count($scenario['inputs']), false);
    if ($scenario['target']) {
        $desired = $scenario['gate'] === 'and' ? array_fill(0, count($desired), true) : array_replace($desired, [0 => true]);
    }
    $program = [];
    foreach ($desired as $index => $value) {
        if ($value !== $scenario['inputs'][$index]) {
            $program[] = 'flip '.($index + 1);
        }
    }

    return $program ?: ['flip 1', 'flip 1'];
}

function towerTextSolution(array $scenario): array
{
    $queue = [[$scenario['start'], []]];
    $visited = [];
    while ($queue !== []) {
        [$text, $program] = array_shift($queue);
        if (str_starts_with($scenario['target'], $text)) {
            $suffix = substr($scenario['target'], strlen($text));
            if (preg_match('/^[A-Za-z0-9_-]*$/D', $suffix)) {
                $append = array_map(fn ($chunk) => 'append '.$chunk, str_split($suffix, 12));

                return [...$program, ...$append] ?: ['upper', 'lower'];
            }
        }
        if (count($program) >= 4) {
            continue;
        }
        foreach (['trim' => trim($text), 'upper' => strtoupper($text), 'lower' => strtolower($text), 'reverse' => strrev($text)] as $command => $result) {
            if (! isset($visited[$result])) {
                $visited[$result] = true;
                $queue[] = [$result, [...$program, $command]];
            }
        }
    }
    throw new RuntimeException('No text solution found.');
}

function towerSolution(array $stage): array
{
    if ($stage['template'] === 'choice-quiz') {
        return $stage['version'] === 1 ? ['answer' => $stage['answer']]
            : ['answers' => array_column($stage['questions'], 'answer', 'id')];
    }
    if ($stage['template'] === 'command-garden') {
        return ['program' => app(GardenLayout::class)->solution($stage), 'repeat' => $stage['mode'] === 'loop', 'conditional' => $stage['mode'] === 'conditional'];
    }
    $scenario = $stage['scenario'];
    $program = match ($stage['template']) {
        'logic-switchboard' => towerCircuitSolution($scenario),
        'string-forge' => towerTextSolution($scenario),
        'memory-trail' => [...array_fill(0, count($scenario['start']), 'pop'), ...array_map(fn ($value) => 'push '.$value, $scenario['target'])],
        'pixel-studio' => array_map(fn ($pixel) => 'paint '.$pixel, $scenario['pixels']),
        'number-machine' => ['add '.($scenario['target'] - $scenario['start'])],
        'terminal-quest' => ['cp '.collect($scenario['files'])->firstWhere('content', $scenario['content'])['name'].' '.$scenario['destination'], 'cat '.$scenario['destination']],
        default => [],
    };
    if ($stage['template'] === 'sort-lab') {
        $items = $scenario['items'];
        $sorted = $items;
        sort($sorted);
        foreach ($sorted as $index => $value) {
            if ($items[$index] !== $value) {
                $position = array_search($value, $items, true);
                $program[] = 'swap '.($index + 1).' '.($position + 1);
                [$items[$index], $items[$position]] = [$items[$position], $items[$index]];
            }
        }
    }

    return ['program' => $program];
}

test('tower installs 50 editable levels without overwriting revisions on reseed', function () {
    $this->seed(TowerLevelSeeder::class);
    $this->seed(TowerLevelSeeder::class);
    $this->assertDatabaseCount('tower_levels', 50);
    $this->assertDatabaseCount('tower_revisions', 50);
    $this->get(route('home'))->assertOk()->assertSee('The Kody Tower')->assertSee('data-tower-stop="50"', false)->assertSee('BOSS');
    $this->get(route('tower.show', TowerLevel::where('position', 4)->sole()))->assertRedirect(route('welcome'));
    $this->get(route('welcome'))->assertOk()->assertSee('data-coding-game', false);
    $this->assertDatabaseCount('tower_clearances', 0);
});

test('all 50 tower levels have typed working solutions and only final boss stages grant streak credit', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    foreach (TowerLevel::with('currentRevision')->orderBy('position')->get() as $level) {
        $stages = app(TowerAuthoring::class)->stages($level->currentRevision->stages);
        foreach ($stages as $index => $stage) {
            $result = app(TowerProgression::class)->complete($user, 'module-test-session', $level, $level->current_revision_id, $index, towerSolution($stage));
            expect($result['tower']['cleared'])->toBe($index === count($stages) - 1);
        }
    }
    $this->assertDatabaseCount('tower_clearances', 50);
    $this->assertDatabaseCount('learning_activity_days', 50);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    expect(app(TowerProgression::class)->snapshot($user->id)['completed_count'])->toBe(50);
});

test('tower cannot skip levels or boss stages or clear a game with a forged empty attempt', function () {
    $this->seed(TowerLevelSeeder::class);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $first = TowerLevel::where('position', 1)->sole();
    $second = TowerLevel::where('position', 2)->sole();
    $this->get(route('tower.show', $second))->assertForbidden();
    $this->postJson(route('tower.complete', [$second, $second->current_revision_id, 0]), towerSolution($second->currentRevision->stages[0]))->assertForbidden();
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), ['answer' => 'a'])->assertUnprocessable();
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), ['program' => ['shell rm -rf /']])->assertUnprocessable();
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), towerSolution($first->currentRevision->stages[0]))->assertOk();
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), towerSolution($first->currentRevision->stages[0]))->assertOk();
    $this->assertDatabaseCount('tower_clearances', 1);
    $this->assertDatabaseCount('tower_stage_completions', 1);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->get(route('tower.show', $second))->assertOk();
});

test('tower boss requires ordered stages and supports safe retry', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    foreach (TowerLevel::where('position', '<', 10)->get() as $prior) {
        DB::table('tower_clearances')->insert(['user_id' => $user->id, 'level_id' => $prior->id, 'revision_id' => $prior->current_revision_id, 'completed_at' => now()]);
    }
    moduleSignIn($this, $user);
    $boss = TowerLevel::where('position', 10)->sole();
    $stages = $boss->currentRevision->stages;
    $this->postJson(route('tower.complete', [$boss, $boss->current_revision_id, 1]), towerSolution($stages[1]))->assertForbidden();
    $this->postJson(route('tower.complete', [$boss, $boss->current_revision_id, 0]), towerSolution($stages[0]))->assertOk()->assertJsonPath('tower.cleared', false);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->postJson(route('tower.complete', [$boss, $boss->current_revision_id, 1]), towerSolution($stages[1]))->assertOk()->assertJsonPath('tower.cleared', true);
    $this->assertDatabaseCount('learning_activity_days', 1);
});

test('only Administrators edit tower revisions and old clearances survive edits', function () {
    $this->seed(TowerLevelSeeder::class);
    $level = TowerLevel::where('position', 1)->sole();
    $learner = moduleAccount(Role::Learner);
    app(TowerProgression::class)->complete($learner, 'module-test-session', $level, $level->current_revision_id, 0, towerSolution($level->currentRevision->stages[0]));
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $data = ['record_version' => 1, 'title' => 'A revised first step', 'concept' => 'Sequences', 'description' => 'An improved prompt.',
        'source_notes' => 'Original Kody scenario.', 'stages_json' => json_encode($level->currentRevision->stages)];
    $this->get(route('tower-studio.edit', $level))->assertOk()->assertDontSee('@include(', false);
    $this->put(route('tower-studio.update', $level), $data)->assertRedirect();
    $this->assertDatabaseCount('tower_revisions', 51);
    expect(app(TowerProgression::class)->snapshot($learner->id)['completed_count'])->toBe(1);
    $this->putJson(route('tower-studio.update', $level), $data)->assertUnprocessable()->assertJsonValidationErrors('record_version');
    moduleSignIn($this, $learner);
    $this->postJson(route('tower.complete', [$level, $level->current_revision_id, 0]), towerSolution($level->currentRevision->stages[0]))->assertConflict();
    $this->get(route('tower-studio.index'))->assertForbidden();
    $this->get(route('tower.show', TowerLevel::where('position', 2)->sole()))->assertOk();
});

test('tower studio rejects malformed quizzes and unsupported executable stages', function (array $stage) {
    $this->seed(TowerLevelSeeder::class);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $level = TowerLevel::where('position', 1)->sole();
    $this->putJson(route('tower-studio.update', $level), ['record_version' => 1, 'title' => 'Bad level', 'concept' => 'Bad', 'description' => 'Invalid stage.',
        'source_notes' => 'Original.', 'stages_json' => json_encode([$stage])])->assertUnprocessable();
    $this->assertDatabaseCount('tower_revisions', 50);
})->with([[['template' => 'javascript', 'source' => 'alert(1)']], [['template' => 'choice-quiz', 'version' => 2, 'title' => 'Empty']]]);

test('creator and staff dashboards omit learner missions and show only their relevant workspace', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    $this->get(route('dashboard'))->assertOk()->assertDontSee('data-progress-strip', false)->assertDontSee('daily-progress', false)
        ->assertDontSee('Your learning journeys')->assertDontSee('level-trail', false);
    if ($role === Role::Instructor) {
        $this->get(route('home'))->assertOk()->assertSee('Kody Tower')->assertSee('data-progress-strip', false);
    } else {
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }
})->with([Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator]);

test('Instructors play the Tower with owned sequential progress and streaks while keeping their creator dashboard', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Instructor);
    moduleSignIn($this, $user);
    $first = TowerLevel::where('position', 1)->sole();
    $second = TowerLevel::where('position', 2)->sole();
    $this->get(route('dashboard'))->assertOk()->assertSee('Play the Tower')->assertDontSee('daily-progress', false);
    $this->get(route('tower.show', $second))->assertForbidden();
    $this->get(route('tower.show', $first))->assertOk();
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), towerSolution($first->currentRevision->stages[0]))->assertOk()->assertJsonPath('tower.cleared', true);
    $this->assertDatabaseHas('tower_clearances', ['user_id' => $user->id, 'level_id' => $first->id]);
    $this->get(route('tower.show', $second))->assertOk();
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('learning_activity_days', 1);
});

test('tower studio bounds nested configuration and account erasure removes saved tower activity', function () {
    $this->seed(TowerLevelSeeder::class);
    $level = TowerLevel::where('position', 1)->sole();
    $user = moduleAccount(Role::Learner);
    app(TowerProgression::class)->complete($user, 'module-test-session', $level, $level->current_revision_id, 0, towerSolution($level->currentRevision->stages[0]));
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('tower_stage_completions', 0);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $json = str_repeat('[', 20).'0'.str_repeat(']', 20);
    $this->putJson(route('tower-studio.update', $level), ['record_version' => 1, 'title' => 'Nested configuration', 'concept' => 'Validation', 'description' => 'Invalid nesting.',
        'source_notes' => 'Original.', 'stages_json' => $json])->assertUnprocessable()->assertJsonValidationErrors('stages_json');
    $this->assertDatabaseCount('tower_revisions', 50);
});
