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

test('tower installs 25 editable levels without overwriting revisions on reseed', function () {
    $this->seed(TowerLevelSeeder::class);
    $this->seed(TowerLevelSeeder::class);
    $this->assertDatabaseCount('tower_levels', 25);
    $this->assertDatabaseCount('tower_revisions', 25);
    $this->get(route('home'))->assertOk()->assertSee('The Kody Tower')->assertSee('data-tower-stop="25"', false)->assertSee('BOSS');
    $this->get(route('tower.show', TowerLevel::where('position', 4)->sole()))->assertRedirect(route('welcome'));
    $this->get(route('welcome'))->assertOk()->assertSee('data-coding-game', false);
    $this->assertDatabaseCount('tower_clearances', 0);
});

test('all 25 tower levels have typed working solutions and only final boss stages grant streak credit', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    foreach (TowerLevel::with('currentRevision')->orderBy('position')->get() as $level) {
        $stages = app(TowerAuthoring::class)->stages($level->currentRevision->stages);
        foreach ($stages as $index => $stage) {
            $result = app(TowerProgression::class)->complete($user, 'module-test-session', $level, $level->current_revision_id, $index, towerSolution($stage));
            expect($result['tower']['cleared'])->toBe($index === count($stages) - 1);
        }
    }
    $this->assertDatabaseCount('tower_clearances', 25);
    $this->assertDatabaseCount('learning_activity_days', 25);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('learning_level_completions', 0);
    expect(app(TowerProgression::class)->snapshot($user->id)['completed_count'])->toBe(25);
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
    $this->assertDatabaseCount('tower_revisions', 26);
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
    $this->assertDatabaseCount('tower_revisions', 25);
})->with([[['template' => 'javascript', 'source' => 'alert(1)']], [['template' => 'choice-quiz', 'version' => 2, 'title' => 'Empty']]]);

test('creator and staff dashboards omit learner missions and show only their relevant workspace', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    $this->get(route('dashboard'))->assertOk()->assertDontSee('data-progress-strip', false)->assertDontSee('daily-progress', false)
        ->assertDontSee('Your learning journeys')->assertDontSee('level-trail', false);
    $this->get(route('home'))->assertRedirect(route('dashboard'));
})->with([Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator]);

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
    $this->assertDatabaseCount('tower_revisions', 25);
});
