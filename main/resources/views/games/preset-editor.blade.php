@extends('layouts.learning')
@section('title', 'Shape a game preset — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('game-presets.index') }}">← Preset workshop</a><h1>{{ $preset ? 'Shape the next version.' : 'Plant a new idea.' }}</h1>
<p>Garden trails share Kody’s movement and objective rules. Practice quizzes support up to ten questions with two to six choices each. Creator lessons use the existing verified Active participant access rules. Validated module wins qualify for daily activity and the platform’s first-completion XP. Presets cannot set extra XP or KodeBit prizes.</p>
@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($preset)<p class="step-pill">{{ $preset->status }} · {{ $uses }} saved module revision references</p>@endif
@php
    $defaults = ['name' => $preset?->name, 'title' => $instance['title'] ?? '', 'instructions' => $instance['instructions'] ?? '',
        'hint' => $instance['hint'] ?? '', 'learning_idea' => $instance['learningIdea'] ?? '', 'question' => $instance['question'] ?? '',
        'choice_a' => $instance['options'][0]['label'] ?? '', 'choice_b' => $instance['options'][1]['label'] ?? '', 'explanation' => $instance['explanation'] ?? ''];
    $basis = $instance['basis'] ?? 'quiz';
@endphp
<form class="studio-form" data-preset-editor method="POST" action="{{ $preset ? route('game-presets.update', $preset) : route('game-presets.store') }}">@csrf @if($preset)@method('PUT')@endif
<input type="hidden" name="record_version" value="{{ $preset?->record_version ?? 1 }}">
<fieldset @disabled($preset?->status === 'Inactive')><legend>Reusable prompts</legend>
<label for="basis">Game rules</label><select id="basis" name="basis">@foreach(['sequences' => 'Sequences garden', 'loops' => 'Loop garden (repeat twice)', 'conditions' => 'Crystal garden (conditional collection)', 'quiz' => 'Practice quiz'] + array_map(fn($game) => $game['title'], config('arcade')) as $value => $label)<option value="{{ $value }}" @selected(old('basis', $preset ? $basis : 'sequences') === $value)>{{ $label }}</option>@endforeach</select>
<p class="field-hint">Fill the game prompts and scenario, or the question, choices and explanation for a quiz. All prompts are plain text.</p>
@foreach(['name' => ['Preset name',100], 'title' => ['Activity title',100]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" maxlength="{{ $limit }}" value="{{ old($name, $defaults[$name]) }}" required>@endforeach
<div data-garden-prompts>@foreach(['instructions' => ['Game instructions',1000], 'hint' => ['Game hint',1000], 'learning_idea' => ['Game learning feedback',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $defaults[$name]) }}</textarea>@endforeach</div>
<div data-quiz-prompts>
@php($quizQuestions = old('quiz_questions', ($instance['template'] ?? null) === 'choice-quiz' ? app(\App\Services\Games\QuizAuthoring::class)->questions($instance) : [['id' => 'q1', 'question' => '', 'options' => [['id' => 'a', 'label' => ''], ['id' => 'b', 'label' => '']], 'answer' => '', 'explanation' => '']]))
@include('games.quiz-editor')
</div>
@include('games.scenario-editor', ['scenarioInstance' => $instance, 'scenarioName' => 'game_scenario'])
<label for="reward_mode">Reward mode</label><select id="reward_mode" name="reward_mode"><option value="Deferred">No extra preset rewards — platform XP applies</option></select>
<button class="button button-play">{{ $preset ? 'Save new version' : 'Create preset' }}</button></fieldset></form>
@if($preset?->status === 'Active')<h2>Retire this preset</h2><p>Inactivation removes it from new module drafts. Existing assessments, references and results stay intact.</p><form class="account-form" method="POST" action="{{ route('game-presets.inactivate', $preset) }}">@csrf<input type="hidden" name="record_version" value="{{ $preset->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> Confirm inactivation</label><button class="button button-dark">Make preset inactive</button></form>@endif
@if($instance)<section class="studio-preview"><h2>Try the saved version</h2><p>Preview wins are local practice.</p>@if($instance['template'] !== 'choice-quiz')@include('learning.activity', ['game' => $instance, 'module' => null, 'completionUrl' => null, 'trial' => false])@else@include('learning.quiz', ['quiz' => $instance, 'module' => null, 'completionUrl' => null])@endif</section>@endif
@if($history)<h2>Preserved versions</h2>@foreach($history as $entry)<p>Version {{ $entry->number }} · {{ $entry->instance['title'] }} · Administrator #{{ $entry->created_by }} · {{ $entry->created_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@endforeach{{ $history->links() }}@endif
</section>
@endsection
