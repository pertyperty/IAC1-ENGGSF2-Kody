<?php

use App\Enums\Role;
use App\Models\ModuleRevision;
use App\Services\Games\AssessmentChallenges;
use App\Services\Games\GameAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('D01 B05 each guided task has a working reference and requires its concept check', function (string $id) {
    $definition = config('assessment-challenges')[$id];
    $game = app(AssessmentChallenges::class)->instance($id);
    $reference = $definition['reference'];
    $engine = app(GameAssessment::class);
    expect($engine->supports($game))->toBeTrue()
        ->and($engine->succeeds($game, $reference))->toBeFalse()
        ->and($engine->succeeds($game, $reference + ['assessment_answer' => 'wrong']))->toBeFalse()
        ->and($engine->succeeds($game, $reference + ['assessment_answer' => $definition['check']['answer']]))->toBeTrue()
        ->and($game)->not->toHaveKeys(['reference']);
})->with(array_keys(require __DIR__.'/../../config/assessment-challenges.php'));

test('D01 guided task selection rejects unknown identities and mismatched templates', function () {
    expect(fn () => app(AssessmentChallenges::class)->instance('number-order.reference'))->toThrow(ValidationException::class);
    expect(fn () => app(GameAssessment::class)->instance(['game_preset' => 'sequences', 'game_challenge' => 'number-order']))
        ->toThrow(ValidationException::class);
});

test('B05 guided completion replays pinned rules and concept answers before awarding once', function () {
    $module = moduleFixture(true, ['game_preset' => 'number-machine', 'game_challenge' => 'number-order']);
    $revision = $module->publishedRevision;
    $snapshot = $revision->assessment;
    expect($snapshot['assessment']['limit'])->toBe(4);
    // Later library edits cannot relax an already published assessment.
    config(['assessment-challenges.number-order.limit' => 12, 'assessment-challenges.number-order.commands' => ['add 16']]);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $this->get(route('modules.show', $module))->assertOk();
    $url = route('modules.game', [$module, $revision->id]);
    $reference = ['program' => ['add 3', 'multiply 2', 'subtract 1', 'multiply 2']];
    $this->postJson($url, $reference)->assertUnprocessable();
    $this->postJson($url, $reference + ['assessment_answer' => 'a'])->assertUnprocessable();
    $this->postJson($url, ['program' => ['add 16'], 'assessment_answer' => 'b', 'assessment' => ['limit' => 12]])->assertUnprocessable();
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->postJson($url, $reference + ['assessment_answer' => 'b'])->assertOk();
    $this->postJson($url, $reference + ['assessment_answer' => 'b'])->assertOk();
    expect(DB::table('xp_awards')->where('user_id', $user->id)->count())->toBe(1);
    expect($revision->fresh()->assessment)->toBe($snapshot);
});

test('D01 HTTP guided authoring derives trusted content and keeps custom activity support', function () {
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $data = moduleData(['game_challenge' => 'stack-recovery', 'game_preset' => 'sequences', 'game_title' => null,
        'game_instructions' => null, 'game_hint' => null, 'game_learning_idea' => null]);
    $this->post(route('studio.store'), $data)->assertRedirect();
    $revision = ModuleRevision::sole();
    expect($revision->assessment['template'])->toBe('memory-trail')
        ->and($revision->assessment['title'])->toBe('Recover the stack')
        ->and($revision->assessment['assessment']['id'])->toBe('stack-recovery');
});
