<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Content\ModulePublishing;
use App\Services\Games\GamePresets;
use App\Services\Games\QuizAuthoring;
use App\Services\Games\TowerAuthoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function mixedQuizQuestions(): array
{
    return config('practice-quizzes.quiz-lab.questions');
}

function mixedQuizAnswer(): array
{
    return ['repeat' => 'true', 'blank' => ' IF ', 'match' => ['loop' => 'loop', 'variable' => 'variable', 'condition' => 'condition']];
}

test('B05 mixed quizzes require all pinned answers and grant an eligible completion only once', function () {
    $module = moduleFixture(true, ['assessment_kind' => 'quiz', 'quiz_questions' => mixedQuizQuestions()]);
    $learner = signInForLearning($this);
    $url = route('modules.quiz', [$module, $module->published_revision_id]);
    $answers = mixedQuizAnswer();
    foreach ([['repeat' => 'true'], array_replace($answers, ['blank' => 'else']), array_replace($answers, ['match' => ['loop' => 'loop']]),
        $answers + ['extra' => 'if'], array_replace($answers, ['match' => ['loop' => ['nested' => 'loop'], 'variable' => 'variable', 'condition' => 'condition']])] as $invalid) {
        $this->postJson($url, ['answers' => $invalid])->assertUnprocessable();
    }
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->get(route('modules.show', $module))->assertOk()->assertSee('Your short answer')->assertSee('data-match-answer', false);
    $this->postJson($url, ['answers' => $answers])->assertOk();
    $this->postJson($url, ['answers' => $answers])->assertOk();
    expect(DB::table('xp_awards')->where('user_id', $learner->id)->count())->toBe(1);
    $this->assertDatabaseCount('learning_activity_days', 1);
});

test('D01 mixed questions reject unsupported and ambiguous author content without saving', function (string $case) {
    $questions = mixedQuizQuestions();
    match ($case) {
        'open-ended' => $questions[1]['type'] = 'open-ended',
        'source' => $questions[1]['source'] = 'alert(1)',
        'duplicate-answer' => $questions[1]['accepted'] = ['if', 'IF'],
        'oversized' => $questions[1]['accepted'] = [str_repeat('x', 101)],
        'blank-answer' => $questions[1]['accepted'] = [' '],
        'duplicate-pair' => $questions[2]['pairs'][1]['id'] = 'loop',
        'ambiguous-pair' => $questions[2]['pairs'][1]['right'] = 'Repeats instructions',
        'nested-answer' => $questions[1]['accepted'] = [['if']],
    };
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->postJson(route('studio.store'), moduleData(['assessment_kind' => 'quiz', 'quiz_questions' => $questions]))->assertUnprocessable();
    $this->assertDatabaseCount('learning_modules', 0);
})->with(['open-ended', 'source', 'duplicate-answer', 'oversized', 'blank-answer', 'duplicate-pair', 'ambiguous-pair', 'nested-answer']);

test('B05 matching questions preserve numeric identifiers across JSON decoding', function () {
    $question = mixedQuizQuestions()[2];
    $question['pairs'] = [['id' => '1', 'left' => 'Loop', 'right' => 'Repeat'], ['id' => '2', 'left' => 'Variable', 'right' => 'Store a value']];
    $module = moduleFixture(true, ['assessment_kind' => 'quiz', 'quiz_questions' => [$question]]);
    signInForLearning($this);
    $this->postJson(route('modules.quiz', [$module, $module->published_revision_id]), ['answers' => ['match' => ['1' => '1', '2' => '2']]])->assertOk();
});

test('D02 G08 mixed presets and published revisions stay pinned during edits and Tower uses the same grading', function () {
    $questions = mixedQuizQuestions();
    $module = moduleFixture(true, ['assessment_kind' => 'quiz', 'quiz_questions' => $questions]);
    $original = $module->publishedRevision;
    $questions[1]['accepted'] = ['else'];
    app(ModulePublishing::class)->save(User::findOrFail($module->created_by), 'module-test-session', moduleData(['record_version' => 3, 'assessment_kind' => 'quiz', 'quiz_questions' => $questions]), $module);
    expect($module->fresh()->published_revision_id)->toBe($original->id)->and(app(QuizAuthoring::class)->succeeds($original->fresh()->assessment, ['answers' => mixedQuizAnswer()]))->toBeTrue();
    $admin = moduleAccount(Role::Administrator);
    $preset = app(GamePresets::class)->save($admin, 'module-test-session', presetData(['basis' => 'quiz', 'quiz_questions' => mixedQuizQuestions()]));
    $copy = moduleFixture(false, ['assessment_kind' => 'preset', 'managed_preset' => $preset->current_revision_id, 'preset_title' => 'Copied quiz']);
    app(GamePresets::class)->save($admin, 'module-test-session', presetData(['name' => $preset->name, 'basis' => 'quiz', 'quiz_questions' => $questions]), $preset);
    expect($copy->latestRevision->assessment['questions'])->toEqual(mixedQuizQuestions());
    $stages = app(TowerAuthoring::class)->stages([$original->assessment]);
    expect(app(QuizAuthoring::class)->succeeds($stages[0], ['answers' => mixedQuizAnswer()]))->toBeTrue();
    $sensitive = mixedQuizQuestions()[1];
    $sensitive['case_sensitive'] = true;
    $quiz = app(QuizAuthoring::class)->instance('Case matters', [$sensitive]);
    expect(app(QuizAuthoring::class)->succeeds($quiz, ['answers' => ['blank' => 'IF']]))->toBeFalse()
        ->and(app(QuizAuthoring::class)->succeeds($quiz, ['answers' => ['blank' => ' if ']]))->toBeTrue();
});

test('G08 every arcade template can be stored as a reusable immutable preset', function (string $basis) {
    $admin = moduleAccount(Role::Administrator);
    $preset = app(GamePresets::class)->save($admin, 'module-test-session', presetData(['basis' => $basis]));
    expect($preset->currentRevision->instance['template'])->toBe($basis);
})->with(['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', 'logic-switchboard', 'string-forge', 'memory-trail']);

test('expanded preset migration refuses rollback when immutable mixed snapshots exist', function () {
    $preset = app(GamePresets::class)->save(moduleAccount(Role::Administrator), 'module-test-session', presetData(['basis' => 'quiz', 'quiz_questions' => mixedQuizQuestions()]));
    $migration = require database_path('migrations/2026_10_11_000003_expand_mixed_quiz_preset_versions.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    expect($preset->fresh()->currentRevision->instance['version'])->toBe(3);
});
