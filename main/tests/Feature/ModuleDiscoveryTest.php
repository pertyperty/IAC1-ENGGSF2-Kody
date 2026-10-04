<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('B01 catalog combines literal search with published assessment template filtering', function () {
    moduleFixture(true, ['title' => 'Terminal delivery', 'game_preset' => 'terminal-quest']);
    moduleFixture(true, ['title' => 'Pixel delivery', 'game_preset' => 'pixel-studio']);
    moduleFixture(true, ['title' => 'Other terminal', 'game_preset' => 'terminal-quest']);
    moduleFixture(false, ['title' => 'Draft delivery', 'game_preset' => 'terminal-quest']);

    $this->get(route('learning.catalog', ['template' => 'terminal-quest', 'q' => 'DELIVERY']))
        ->assertOk()->assertSee('Terminal delivery')->assertDontSee('Pixel delivery')
        ->assertDontSee('Other terminal')->assertDontSee('Draft delivery')->assertDontSee('First steps');
    $this->get(route('learning.catalog', ['q' => '%']))->assertOk()->assertSee('No adventures match');
});

test('B01 discovery excludes archived withdrawn and unpublished replacement revisions', function () {
    $archived = moduleFixture(true, ['title' => 'Archived terminal', 'game_preset' => 'terminal-quest']);
    $archived->forceFill(['status' => 'Archived'])->save();
    $withdrawn = moduleFixture(true, ['title' => 'Withdrawn terminal', 'game_preset' => 'terminal-quest']);
    $withdrawn->forceFill(['staff_withdrawn_at' => now()])->save();
    $visible = moduleFixture(true, ['title' => 'Published terminal', 'game_preset' => 'terminal-quest']);
    $draft = $visible->publishedRevision->replicate();
    $draft->forceFill(['number' => 2, 'title' => 'Replacement pixels', 'review_status' => 'Draft', 'assessment' => ['template' => 'pixel-studio']])->save();

    $this->get(route('learning.catalog', ['template' => 'terminal-quest']))->assertOk()
        ->assertSee('Published terminal')->assertDontSee('Archived terminal')->assertDontSee('Withdrawn terminal')->assertDontSee('Replacement pixels');
    $this->get(route('learning.catalog', ['template' => 'pixel-studio']))->assertOk()->assertSee('No adventures match');
});

test('B01 starter trails expose both their garden and quiz activities', function () {
    foreach (['command-garden', 'choice-quiz'] as $template) {
        $this->get(route('learning.catalog', ['template' => $template]))->assertOk()->assertSee('First steps')->assertSee('The loop trail');
    }
    $this->getJson(route('learning.catalog', ['template' => 'uploaded-script']))->assertUnprocessable()->assertJsonValidationErrors('template');
    $this->getJson(route('learning.catalog', ['template' => ['pixel-studio']]))->assertUnprocessable()->assertJsonValidationErrors('template');
});

test('B01 guest practice links to matching lessons while lesson access still requires sign in', function () {
    $module = moduleFixture(true, ['game_preset' => 'terminal-quest']);
    $this->get(route('arcade', ['game' => 'terminal-quest']))->assertOk()
        ->assertSee(route('learning.catalog', ['template' => 'terminal-quest']), false);
    $this->get(route('modules.show', $module))->assertRedirect(route('login'));
});

test('B01 pagination retains both discovery filters', function () {
    for ($index = 0; $index < 13; $index++) {
        moduleFixture(true, ['title' => 'Terminal lesson '.$index, 'game_preset' => 'terminal-quest']);
    }
    $response = $this->get(route('learning.catalog', ['template' => 'terminal-quest', 'q' => 'lesson']));
    $response->assertOk()->assertViewHas('published', fn ($results) => $results->total() === 13 && $results->count() === 12)
        ->assertSee('template=terminal-quest', false)->assertSee('q=lesson', false);
});
