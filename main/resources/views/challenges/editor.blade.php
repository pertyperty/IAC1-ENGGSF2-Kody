@extends('layouts.learning')
@section('title', 'Shape a coding quest — Kody')
@section('content')


<section class="review-page page-width">
@if($challenge?->isWithdrawn())<p class="lesson-note">Staff withdrawal is active. Learner access is blocked. Draft corrections, publication review and creator archival do not lift this block; only staff restoration can do that.</p>@endif
<a class="quiet-link" href="{{ route('challenges.index') }}">← Your challenges</a><h1>Turn a problem into possibility.</h1><p>Help someone discover what their code can do.</p>
@if(!empty($starter))<p class="lesson-note">Your example is ready to customize. It has not been saved or published. Check the problem, sample cases and hidden edge cases before submitting a draft for review.</p>@endif
@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($revision)<p class="step-pill">{{ $challenge->status }} · Revision {{ $revision->number }} · {{ $revision->review_status }}</p>@if($revision->review_notes)<p class="lesson-note">{{ $revision->review_notes }}</p>@endif @endif
<form class="studio-form" method="post" action="{{ $challenge ? route('challenges.update', $challenge) : route('challenges.store') }}">@csrf @if($challenge)@method('put')@endif<input type="hidden" name="record_version" value="{{ $challenge?->record_version ?? 1 }}">
<fieldset @disabled($revision?->review_status === 'Pending' || $challenge?->status === 'Archived')><legend>Your challenge</legend>
<label for="title">Quest title</label><input id="title" name="title" maxlength="150" value="{{ old('title', $revision?->title ?? ($starter['title'] ?? null)) }}" required>
<label for="description">The problem to solve</label><textarea id="description" name="description" rows="6" maxlength="50000" required>{{ old('description', $revision?->description ?? ($starter['description'] ?? null)) }}</textarea>
<label for="language">Programming language</label><select id="language" name="language">@foreach(config('challenges.languages') as $key => $label)<option value="{{ $key }}" @selected(old('language', $revision?->language ?? ($starter['language'] ?? null) ?? 'python') === $key)>{{ $label }}</option>@endforeach</select>
<label for="difficulty">Difficulty</label><select id="difficulty" name="difficulty">@foreach(['Easy','Medium','Hard'] as $difficulty)<option @selected(old('difficulty', $revision?->difficulty ?? ($starter['difficulty'] ?? null) ?? 'Easy') === $difficulty)>{{ $difficulty }}</option>@endforeach</select>
<label for="category">Topic</label><select id="category" name="category">@foreach(config('challenges.categories') as $key => $label)<option value="{{ $key }}" @selected(old('category', $revision?->category ?? 'foundations') === $key)>{{ $label }}</option>@endforeach</select>
<fieldset><legend>Concept tags (choose up to five)</legend>@foreach(config('challenges.tags') as $key => $label)<label><input type="checkbox" name="tags[]" value="{{ $key }}" @checked(in_array($key, (array) old('tags', $revision?->tags ?? []), true))> {{ $label }}</label>@endforeach</fieldset>
<label for="rules">Rules and constraints</label><textarea id="rules" name="rules" rows="4" maxlength="20000" required>{{ old('rules', $revision?->rules ?? ($starter['rules'] ?? null)) }}</textarea>
<label for="input_format">What does the program receive?</label><textarea id="input_format" name="input_format" rows="3" maxlength="5000" required>{{ old('input_format', $revision?->input_format ?? ($starter['input_format'] ?? null)) }}</textarea>
<label for="output_format">What should the program print?</label><textarea id="output_format" name="output_format" rows="3" maxlength="5000" required>{{ old('output_format', $revision?->output_format ?? ($starter['output_format'] ?? null)) }}</textarea>
<label for="cpu_time_ms">CPU time per test (milliseconds)</label><input id="cpu_time_ms" name="cpu_time_ms" type="number" min="{{ config('challenges.authoring.min_cpu_time_ms') }}" max="{{ config('challenges.authoring.max_cpu_time_ms') }}" value="{{ old('cpu_time_ms', $revision?->cpu_time_ms ?? ($starter['cpu_time_ms'] ?? null) ?? 1000) }}" required>
<label for="memory_kib">Memory per test (KiB)</label><input id="memory_kib" name="memory_kib" type="number" min="{{ config('challenges.authoring.min_memory_kib') }}" max="{{ config('challenges.authoring.max_memory_kib') }}" value="{{ old('memory_kib', $revision?->memory_kib ?? ($starter['memory_kib'] ?? null) ?? 262144) }}" required>
<section data-test-case-editor data-max-cases="{{ config('challenges.authoring.max_test_cases') }}"><h2>Give your quest a few checks.</h2><p class="field-hint">Sample checks appear to learners. Hidden checks are shared only with you and reviewers. Keep spaces and line breaks exactly as the program expects. Empty input or output is allowed.</p>
@php
    $savedCases = $revision?->testCases->map(fn ($case) => ['input' => $case->input, 'expected_output' => $case->expected_output, 'hidden' => $case->hidden])->all() ?? ($starter['test_cases'] ?? [['input' => '', 'expected_output' => '', 'hidden' => false]]);
    $cases = old('test_cases', $savedCases);
    $cases = is_array($cases) ? array_slice(array_values($cases), 0, config('challenges.authoring.max_test_cases')) : $savedCases;
@endphp
<div data-test-cases>@foreach($cases as $index => $case)
@php $case = is_array($case) ? $case : []; @endphp
<fieldset class="challenge-case" data-test-case><legend>Check <span data-case-number>{{ $index + 1 }}</span></legend>
<label>Input<textarea name="test_cases[{{ $index }}][input]" data-case-field="input" rows="3" maxlength="{{ config('challenges.authoring.max_case_characters') }}">{{ "\n".(is_scalar($case['input'] ?? null) ? $case['input'] : '') }}</textarea></label>
<label>Expected output<textarea name="test_cases[{{ $index }}][expected_output]" data-case-field="expected_output" rows="3" maxlength="{{ config('challenges.authoring.max_case_characters') }}">{{ "\n".(is_scalar($case['expected_output'] ?? null) ? $case['expected_output'] : '') }}</textarea></label>
<label>Visibility<select name="test_cases[{{ $index }}][hidden]" data-case-field="hidden"><option value="0" @selected(!($case['hidden'] ?? false))>Sample · Learners can see this</option><option value="1" @selected($case['hidden'] ?? false)>Hidden · Creator and reviewers only</option></select></label><button type="button" class="game-reset" data-remove-case>Remove check</button></fieldset>
@endforeach</div>
<template data-test-case-template><fieldset class="challenge-case" data-test-case><legend>Check <span data-case-number></span></legend><label>Input<textarea data-case-field="input" rows="3" maxlength="{{ config('challenges.authoring.max_case_characters') }}"></textarea></label><label>Expected output<textarea data-case-field="expected_output" rows="3" maxlength="{{ config('challenges.authoring.max_case_characters') }}"></textarea></label><label>Visibility<select data-case-field="hidden"><option value="0">Sample · Learners can see this</option><option value="1" selected>Hidden · Creator and reviewers only</option></select></label><button type="button" class="game-reset" data-remove-case>Remove check</button></fieldset></template>
<button type="button" class="button button-dark button-small" data-add-case>Add a check +</button><p data-case-feedback role="status" class="field-hint"></p></section>
@include('transactions.access-settings', ['accessKind' => 'challenge'])
<div class="studio-actions"><button class="button button-play">Save challenge draft</button><a class="quiet-link" href="{{ route('challenges.index') }}">Cancel</a></div></fieldset></form>
@if($revision?->review_status === 'Draft' && $challenge?->status !== 'Archived')<form method="post" action="{{ route('challenges.submit', $challenge) }}" class="studio-actions">@csrf<input type="hidden" name="record_version" value="{{ $challenge->record_version }}"><button class="button button-dark">Submit saved challenge for review →</button></form>@endif
@can('archive', $challenge)<p><a class="quiet-link" href="{{ route('challenges.archive-confirmation', $challenge) }}">Archive this challenge</a></p>@endcan
@if($challenge?->status === 'Archived')<p class="lesson-note">This challenge is archived. Its revisions and review history are preserved.</p>@endif
<p class="field-hint">Approved quests appear in the coding catalog. Full code evaluation will open when the platform's execution service is ready.</p>@if($challenge)@can('delete', $challenge)<p><a class="quiet-link" href="{{ route('content-deletion.show', ['challenge', $challenge->id]) }}">Delete this challenge →</a></p>@endcan
@endif
</section>
@endsection
