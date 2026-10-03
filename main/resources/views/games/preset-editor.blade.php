@extends('layouts.learning')
@section('title', 'Shape a game preset — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('game-presets.index') }}">← Preset workshop</a><h1>{{ $preset ? 'Shape the next version.' : 'Plant a new idea.' }}</h1>
<p>Garden trails share Kody’s movement and objective rules. Practice quizzes have two choices and one correct answer. Creator lessons use the existing verified Active participant access rules. Wins qualify existing learning activity; XP and KodeBit rewards are deferred.</p>
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
<label for="basis">Game rules</label><select id="basis" name="basis">@foreach(['sequences' => 'Sequences garden', 'loops' => 'Loop garden (repeat twice)', 'conditions' => 'Crystal garden (conditional collection)', 'quiz' => 'Two-choice practice quiz'] as $value => $label)<option value="{{ $value }}" @selected(old('basis', $preset ? $basis : 'sequences') === $value)>{{ $label }}</option>@endforeach</select>
<p class="field-hint">Fill the garden prompts for a trail, or the question, choices and explanation for a quiz. All prompts are plain text.</p>
@foreach(['name' => ['Preset name',100], 'title' => ['Activity title',100]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" maxlength="{{ $limit }}" value="{{ old($name, $defaults[$name]) }}" required>@endforeach
<div data-garden-prompts>@foreach(['instructions' => ['Garden instructions',1000], 'hint' => ['Garden hint',1000], 'learning_idea' => ['Garden learning feedback',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $defaults[$name]) }}</textarea>@endforeach</div>
<div data-quiz-prompts>@foreach(['question' => ['Quiz question',500], 'choice_a' => ['Quiz choice A',300], 'choice_b' => ['Quiz choice B',300], 'explanation' => ['Quiz explanation',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $defaults[$name]) }}</textarea>@endforeach
<label for="answer">Correct quiz choice</label><select id="answer" name="answer"><option value="a" @selected(old('answer', $instance['answer'] ?? 'a') === 'a')>Choice A</option><option value="b" @selected(old('answer', $instance['answer'] ?? 'a') === 'b')>Choice B</option></select>
</div>
<label for="reward_mode">Reward mode</label><select id="reward_mode" name="reward_mode"><option value="Deferred">Deferred — no XP or KodeBit grants</option></select>
<button class="button button-play">{{ $preset ? 'Save new version' : 'Create preset' }}</button></fieldset></form>
@if($preset?->status === 'Active')<h2>Retire this preset</h2><p>Inactivation removes it from new module drafts. Existing assessments, references and results stay intact.</p><form class="account-form" method="POST" action="{{ route('game-presets.inactivate', $preset) }}">@csrf<input type="hidden" name="record_version" value="{{ $preset->record_version }}"><label><input type="checkbox" name="confirmed" value="1" required> Confirm inactivation</label><button class="button button-dark">Make preset inactive</button></form>@endif
@if($instance)<section class="studio-preview"><h2>Try the saved version</h2><p>Preview wins are local practice.</p>@if($instance['template'] === 'command-garden')@include('learning.game', ['game' => $instance, 'module' => null, 'completionUrl' => null, 'trial' => false])@else@include('learning.quiz', ['quiz' => $instance, 'module' => null, 'completionUrl' => null])@endif</section>@endif
@if($history)<h2>Preserved versions</h2>@foreach($history as $entry)<p>Version {{ $entry->number }} · {{ $entry->instance['title'] }} · Administrator #{{ $entry->created_by }} · {{ $entry->created_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@endforeach{{ $history->links() }}@endif
</section>
@endsection
