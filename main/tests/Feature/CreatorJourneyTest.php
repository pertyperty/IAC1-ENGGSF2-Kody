<?php

use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Content\CreatorExamples;
use App\Services\Gamification\LearningProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('D01 guided examples render and save editable drafts without publishing', function (string $slug) {
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $fields = app(CreatorExamples::class)->fields($slug);
    $this->get(route('studio.create', ['example' => $slug]))->assertOk()->assertSee($fields['title'])
        ->assertSee('It has not been saved or published yet');
    $this->assertDatabaseCount('learning_modules', 0);
    $this->post(route('studio.store'), $fields + ['record_version' => 1])->assertRedirect();
    $module = LearningModule::sole();
    expect($module->status)->toBe('Draft')->and($module->published_revision_id)->toBeNull();
    $this->get(route('studio.edit', $module))->assertOk()->assertSee($fields['title'])->assertDontSee('data-completion-url', false);
})->with(['sequences', 'loops', 'conditions', 'pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', 'choice-quiz']);

test('D01 example selection preserves role restrictions and rejects unknown examples', function () {
    $this->get(route('studio.create', ['example' => 'sequences']))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('studio.create', ['example' => 'sequences']))->assertForbidden();
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->getJson(route('studio.create', ['example' => 'executable']))->assertUnprocessable()->assertJsonValidationErrors('example');
    $this->get(route('studio.index'))->assertOk()->assertSee('Start with a ready-to-play idea');
});

test('B05 next adventure and daily feedback follow persisted validated progress', function () {
    $user = signInForLearning($this);
    $this->get(route('dashboard'))->assertOk()->assertSee('Start your first saved adventure');
    expect(app(LearningProgression::class)->snapshot($user->id))->toMatchArray(['next_level' => 'sequences', 'active_today' => false]);
    $this->postJson(route('play.quiz', 'sequences'), ['answer' => 'order'])->assertOk()->assertJsonPath('progress.next_level', 'sequences');
    $this->get(route('dashboard'))->assertSee('Today’s practice is saved');
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertOk()->assertJsonPath('progress.next_level', 'loops');
    $this->postJson(route('play.game', 'loops'), ['program' => ['right', 'up', 'right'], 'repeat' => true])->assertOk();
    $this->postJson(route('play.game', 'conditions'), sequenceSolution() + ['conditional' => true])->assertOk()->assertJsonPath('progress.next_level', null);
    $this->get(route('dashboard'))->assertSee('You cleared your starter trail');
    $this->travel(2)->days();
    expect(app(LearningProgression::class)->snapshot($user->id))->toMatchArray(['next_level' => null, 'active_today' => false, 'current_streak' => 0]);
});

test('B01 landing trial invites guests to save and signed in players to continue', function () {
    $this->get(route('home'))->assertOk()->assertSee('This trial stays here')->assertSee('Start your saved adventure');
    signInForLearning($this);
    $this->get(route('home'))->assertOk()->assertSee('Continue your saved adventure')->assertSee('Continue your saved trail');
    $this->get(route('learning.show', 'sequences'))->assertOk()->assertSee('Continue playing');
});

test('A01 A02 B03 B05 registration to verified play enrollment and assessment works end to end', function () {
    $course = courseFixture(true);
    $this->post(route('register.store'), registrationData())->assertRedirect();
    $accountId = User::where('email', 'learner@example.test')->value('id');
    $delivery = VerificationDelivery::where('user_id', $accountId)->sole();
    $this->post(route('verification.verify'), ['verification_token' => $delivery->token])->assertOk();
    $this->post(route('login.store'), ['email' => 'learner@example.test', 'password' => 'StrongPass12!'])->assertRedirect(route('dashboard'));
    $this->withCredentials()->withCookie(config('session.cookie'), session()->getId());
    $this->postJson(route('play.game', 'sequences'), sequenceSolution())->assertOk()->assertJsonPath('progress.next_level', 'loops');
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertRedirect();
    $slot = $course->publishedRevision->modules->sole();
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk()->assertSee('<h1>Robot picnic</h1>', false);
    $this->postJson(route('course-learning.game', [$course, $slot->id]), sequenceSolution())->assertOk()->assertJsonPath('progress.active_today', true);
    $this->assertDatabaseCount('course_enrollments', 1);
});
