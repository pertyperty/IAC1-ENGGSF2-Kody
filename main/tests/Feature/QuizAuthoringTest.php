<?php

use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Content\ModulePublishing;
use App\Services\Games\GamePresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function authoredQuizQuestions(): array
{
    return [
        ['id' => 'order', 'question' => 'Which runs first?', 'options' => [
            ['id' => 'third', 'label' => 'The third instruction'], ['id' => 'first', 'label' => 'The first instruction'],
            ['id' => 'last', 'label' => 'The last instruction']], 'answer' => 'first', 'explanation' => 'Start at the beginning.'],
        ['id' => 'loop', 'question' => 'What repeats?', 'options' => [['id' => 'loop', 'label' => 'A loop'], ['id' => 'variable', 'label' => 'A variable']],
            'answer' => 'loop', 'explanation' => 'Loops repeat instructions.'],
    ];
}

test('D01 D02 expanded quizzes render edit and retain the original approved revision during replacement', function () {
    $module = moduleFixture(true, ['assessment_kind' => 'quiz', 'quiz_questions' => authoredQuizQuestions()]);
    $original = $module->publishedRevision;
    $replacement = array_reverse(authoredQuizQuestions());
    $replacement[0]['options'] = array_reverse($replacement[0]['options']);
    app(ModulePublishing::class)->save(User::findOrFail($module->created_by), 'module-test-session', moduleData(['record_version' => 3,
        'assessment_kind' => 'quiz', 'quiz_questions' => $replacement]), $module);
    expect($module->fresh()->published_revision_id)->toBe($original->id)
        ->and($original->fresh()->assessment['questions'])->toEqual(authoredQuizQuestions());
    moduleSignIn($this, User::findOrFail($module->created_by));
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('What repeats?')->assertSee('The third instruction')
        ->assertDontSee('data-completion-url', false);
});

test('D01 authoring accepts two to six choices and keeps one-question version one compatible', function (int $count) {
    $question = authoredQuizQuestions()[0];
    $question['options'] = array_map(fn ($index) => ['id' => 'option'.$index, 'label' => 'Choice '.$index], range(1, $count));
    $question['answer'] = 'option'.$count;
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->post(route('studio.store'), moduleData(['assessment_kind' => 'quiz', 'quiz_questions' => [$question]]))->assertRedirect();
    $instance = LearningModule::sole()->latestRevision->assessment;
    expect($instance['version'])->toBe(1)->and($instance['options'])->toBe($question['options'])->and($instance['answer'])->toBe('option'.$count);
})->with([2, 3, 4, 5, 6]);

test('D01 authoring rejects malformed question collections without saving', function (string $case) {
    $questions = authoredQuizQuestions();
    match ($case) {
        'too-many' => $questions = array_fill(0, 11, $questions[0]),
        'duplicate-ids' => $questions[1]['id'] = $questions[0]['id'],
        'duplicate-options' => $questions[0]['options'][1]['id'] = $questions[0]['options'][0]['id'],
        'duplicate-labels' => $questions[0]['options'][1]['label'] = ' '.$questions[0]['options'][0]['label'].' ',
        'missing-answer' => $questions[0]['answer'] = 'unknown',
        'extra-source' => $questions[0]['source'] = 'alert(1)',
        'oversized' => $questions[0]['question'] = str_repeat('x', 501),
        'scalar' => $questions[0] = 'wrong',
        'nested-id' => $questions[0]['options'][0]['id'] = ['bad'],
    };
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->postJson(route('studio.store'), moduleData(['assessment_kind' => 'quiz', 'quiz_questions' => $questions]))->assertUnprocessable();
    $this->assertDatabaseCount('learning_modules', 0);
})->with(['too-many', 'duplicate-ids', 'duplicate-options', 'duplicate-labels', 'missing-answer', 'extra-source', 'oversized', 'scalar', 'nested-id']);

test('B05 multiquestion completion requires every pinned answer and saves once', function () {
    $module = moduleFixture(true, ['assessment_kind' => 'quiz', 'quiz_questions' => authoredQuizQuestions()]);
    $learner = signInForLearning($this);
    $url = route('modules.quiz', [$module, $module->published_revision_id]);
    foreach ([['order' => 'first'], ['order' => 'third', 'loop' => 'loop'], ['order' => 'first', 'loop' => 'loop', 'extra' => 'first']] as $answers) {
        $this->postJson($url, ['answers' => $answers])->assertUnprocessable();
    }
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->postJson($url, ['answers' => ['order' => 'first', 'loop' => 'loop']])->assertOk();
    $this->postJson($url, ['answers' => ['loop' => 'loop', 'order' => 'first']])->assertOk();
    $this->assertDatabaseCount('learning_activity_days', 1);
    expect(DB::table('learning_progress')->where('user_id', $learner->id)->value('current_streak'))->toBe(1);
});

test('G08 expanded quiz presets retain immutable instances in modules after updates', function () {
    $admin = moduleAccount(Role::Administrator);
    $preset = app(GamePresets::class)->save($admin, 'module-test-session', presetData(['basis' => 'quiz', 'quiz_questions' => authoredQuizQuestions()]));
    $module = moduleFixture(false, ['assessment_kind' => 'preset', 'managed_preset' => $preset->current_revision_id, 'preset_title' => 'My copied quiz']);
    $questions = authoredQuizQuestions();
    $questions[0]['question'] = 'A new question';
    app(GamePresets::class)->save($admin, 'module-test-session', presetData(['name' => $preset->name, 'basis' => 'quiz', 'quiz_questions' => $questions]), $preset);
    expect($module->latestRevision->assessment['questions'])->toEqual(authoredQuizQuestions());
    moduleSignIn($this, $admin);
    $this->get(route('game-presets.edit', $preset))->assertOk()->assertSee('A new question')->assertSee('The third instruction');
});
