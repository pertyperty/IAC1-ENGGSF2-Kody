@extends('layouts.learning')
@section('title', ($preset ? 'Edit game preset' : 'Create game preset').' — Kody')
@section('content')
@pushOnce('page-scripts', 'authoring')
    @vite('resources/js/authoring.js')
@endPushOnce
<section class="review-page page-width"><a class="quiet-link" href="{{ route('game-presets.index') }}"><x-action-icon name="left"/> Game presets</a><h1>{{ $preset ? 'Edit game preset' : 'Create game preset' }}</h1>
<p>Save a reusable activity for lesson creators. Choose its type, set a goal, then try it.</p>

@include('layouts.form-errors')
@if($preset)<p class="step-pill">{{ $preset->status }} · {{ $uses }} saved lesson revisions using this preset</p>@endif
@php
    $defaults = ['name' => $preset?->name, 'title' => $instance['title'] ?? '', 'instructions' => $instance['instructions'] ?? '',
        'hint' => $instance['hint'] ?? '', 'learning_idea' => $instance['learningIdea'] ?? '', 'question' => $instance['question'] ?? '',
        'choice_a' => $instance['options'][0]['label'] ?? '', 'choice_b' => $instance['options'][1]['label'] ?? '', 'explanation' => $instance['explanation'] ?? ''];
    $basis = $instance['basis'] ?? 'quiz';
@endphp
<form class="studio-form creator-editor" data-preset-editor method="POST" action="{{ $preset ? route('game-presets.update', $preset) : route('game-presets.store') }}">@csrf @if($preset)@method('PUT')@endif
<input type="hidden" name="record_version" value="{{ $preset?->record_version ?? 1 }}">
<fieldset @disabled($preset?->status === 'Inactive')><legend>Game preset</legend>
<label for="basis">Activity type</label><select id="basis" name="basis">@foreach(['sequences' => 'Sequences garden', 'loops' => 'Loop garden (repeat twice)', 'conditions' => 'Crystal garden (conditional collection)', 'quiz' => 'Practice quiz'] + array_map(fn($game) => $game['title'], config('arcade')) as $value => $label)<option value="{{ $value }}" @selected(old('basis', $preset ? $basis : 'sequences') === $value)>{{ $label }}</option>@endforeach</select>
<p class="field-hint">Choose a game or quiz that checks the lesson's learning goal.</p>
@foreach(['name' => ['Preset name',100], 'title' => ['Activity title',100]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" maxlength="{{ $limit }}" value="{{ old($name, $defaults[$name]) }}" required>@endforeach
<div data-garden-prompts>@foreach(['instructions' => ['Game instructions',1000], 'hint' => ['Game hint',1000], 'learning_idea' => ['What should learners understand?',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $defaults[$name]) }}</textarea>@endforeach</div>
<div data-quiz-prompts>
@php($quizQuestions = old('quiz_questions', ($instance['template'] ?? null) === 'choice-quiz' ? app(\App\Services\Games\QuizAuthoring::class)->questions($instance) : [['id' => 'q1', 'question' => '', 'options' => [['id' => 'a', 'label' => ''], ['id' => 'b', 'label' => '']], 'answer' => '', 'explanation' => '']]))
@include('games.quiz-editor')
</div>
@include('games.scenario-editor', ['scenarioInstance' => $instance, 'scenarioName' => 'game_scenario'])
<input type="hidden" name="reward_mode" value="Deferred">
<details class="access-options"><summary>How presets work</summary><p>Creators can add this activity to a lesson. Changes make a new version; saved lesson revisions keep their current activity.</p><p>Lesson completion follows platform reward rules. Presets cannot add XP or KodeBit prizes. Previews save no progress or rewards.</p></details>
<button class="button button-play">{{ $preset ? 'Save new version' : 'Create preset' }}</button></fieldset></form>
@if($preset?->status === 'Active')<h2>Retire this preset</h2><p>Retiring removes it from new lesson drafts. Existing lessons and results keep their saved version.</p><form class="account-form" method="POST" action="{{ route('game-presets.inactivate', $preset) }}">@csrf<input type="hidden" name="record_version" value="{{ $preset->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> I want to retire this preset</label><button class="button button-dark">Retire preset</button></form>@endif
@if($instance)<section class="studio-preview"><h2>Try the saved version</h2><p>Preview wins are local practice.</p>@if($instance['template'] !== 'choice-quiz')@include('learning.activity', ['game' => $instance, 'module' => null, 'completionUrl' => null, 'trial' => false])@else
@include('learning.quiz', ['quiz' => $instance, 'module' => null, 'completionUrl' => null])@endif</section>@endif
@if($history)<h2>Version history</h2>@foreach($history as $entry)<p>Version {{ $entry->number }} · {{ $entry->instance['title'] }} · Administrator #{{ $entry->created_by }} · {{ $entry->created_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@endforeach{{ $history->links() }}@endif
</section>
@endsection
