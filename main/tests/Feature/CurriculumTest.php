<?php

use App\Enums\Role;
use App\Models\CodingChallenge;
use App\Models\LearningModule;
use App\Services\Content\CreatorExamples;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('D01 D05 curriculum plans open unsaved lessons and course outlines behind instructor authorization', function () {
    $this->get(route('curriculum.index'))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Contributor));
    $this->get(route('curriculum.index'))->assertForbidden();
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->get(route('curriculum.index'))->assertOk()->assertSee('Your first programming adventures')->assertSee('Make data do something');
    foreach (config('curriculum') as $slug => $pack) {
        $this->get(route('courses.create', ['curriculum' => $slug]))->assertOk()->assertSee($pack['title'])->assertSee('This course plan is unsaved');
        foreach ($pack['lessons'] as $example) {
            $fields = app(CreatorExamples::class)->fields($example);
            $this->get(route('studio.create', ['example' => $example]))->assertOk()->assertSee($fields['title'])->assertDontSee('data-completion-url', false);
        }
    }
    $this->assertDatabaseCount('learning_modules', 0);
    $this->assertDatabaseCount('learning_courses', 0);
    $this->getJson(route('courses.create', ['curriculum' => 'bad']))->assertUnprocessable();
});

test('D01 new reading and capstone examples save valid editable drafts', function (string $slug) {
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $fields = app(CreatorExamples::class)->fields($slug);
    $this->post(route('studio.store'), $fields + ['record_version' => 1])->assertRedirect();
    $module = LearningModule::sole();
    expect($module->status)->toBe('Draft')->and($module->published_revision_id)->toBeNull();
    if ($fields['assessment_kind'] === 'none') {
        expect($module->latestRevision->assessment)->toBeNull();
    } else {
        expect($module->latestRevision->assessment['version'])->toBe(2)->and($module->latestRevision->assessment['questions'])->toHaveCount(3);
    }
})->with(['welcome-code', 'data-introduction', 'programming-check', 'data-check']);

test('C01 original challenge examples support each approved language and do not save on opening', function (string $slug, string $language) {
    moduleSignIn($this, moduleAccount(Role::Contributor));
    $example = config('challenge-examples.'.$slug);
    $this->get(route('challenges.create', ['example' => $slug, 'language' => $language]))->assertOk()->assertSee($example['title'])->assertSee('It has not been saved or published');
    $this->assertDatabaseCount('coding_challenges', 0);
    $this->post(route('challenges.store'), $example + ['record_version' => 1, 'language' => $language, 'difficulty' => 'Easy', 'cpu_time_ms' => 1000, 'memory_kib' => 262144])->assertRedirect();
    $challenge = CodingChallenge::sole();
    expect($challenge->status)->toBe('Draft')->and($challenge->latestRevision->language)->toBe($language);
    expect($challenge->latestRevision->testCases()->where('hidden', true)->count())->toBeGreaterThan(0);
})->with(['sum-two', 'even-or-odd', 'countdown'])->with(['python', 'java', 'cpp']);

test('C01 challenge example test outputs cover deterministic beginner edge cases', function () {
    foreach (config('challenge-examples') as $slug => $example) {
        foreach ($example['test_cases'] as $case) {
            $values = array_map('intval', preg_split('/\s+/', trim($case['input'])));
            $expected = match ($slug) {
                'sum-two' => array_sum($values)."\n",
                'even-or-odd' => ($values[0] % 2 === 0 ? 'EVEN' : 'ODD')."\n",
                'countdown' => ($values[0] === 0 ? '' : implode("\n", range($values[0], 1))."\n")."GO!\n",
            };
            expect($case['expected_output'])->toBe($expected);
        }
    }
});

test('C01 challenge examples retain actor boundaries and reject unsupported selectors', function () {
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('challenges.create', ['example' => 'sum-two']))->assertForbidden();
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->getJson(route('challenges.create', ['example' => 'bad']))->assertUnprocessable();
    $this->getJson(route('challenges.create', ['example' => 'sum-two', 'language' => 'javascript']))->assertUnprocessable();
});
