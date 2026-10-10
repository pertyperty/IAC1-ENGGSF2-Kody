@extends('layouts.learning')
@section('title', 'Edit tower level '.$level->position.' — Kody')
@section('content')
<section class="review-page page-width">
    <p class="overline">TOWER STUDIO · LEVEL {{ $level->position }}</p>
    <h1>Build a meaningful challenge.</h1>
    <p>Edit prompts, scenarios and stages. Publishing starts a new immutable revision. An open attempt on an older revision must reload; saved level clears remain intact.</p>
    @include('layouts.form-errors')
    <nav class="editor-jumps" aria-label="Tower editing steps">
        <a href="#tower-prompts">01 · Story</a><a href="#tower-config">02 · Stages</a><a href="#tower-sources">03 · Sources</a><a href="#tower-preview">04 · Saved preview</a>
    </nav>
    <form class="studio-form tower-authoring" method="POST" action="{{ route('tower-studio.update', $level) }}" data-tower-editor>
        @csrf
        @method('PUT')
        <input type="hidden" name="record_version" value="{{ $level->record_version }}">
        <div class="tower-story-pane">
            <fieldset id="tower-prompts">
                <legend>The learner’s invitation</legend>
                @foreach(['title' => 'Level title', 'concept' => 'Topic / language'] as $name => $label)
                    <label for="tower-{{ $name }}">{{ $label }}</label>
                    <input id="tower-{{ $name }}" name="{{ $name }}" maxlength="100" value="{{ old($name, $revision->$name) }}" required>
                @endforeach
                <label for="tower-description">Learning objective</label>
                <textarea id="tower-description" name="description" maxlength="1000" rows="4" required>{{ old('description', $revision->description) }}</textarea>
            </fieldset>
            <fieldset id="tower-sources">
                <legend>Editorial provenance</legend>
                <label for="source-notes">Source links, licenses and adaptation notes</label>
                <textarea id="source-notes" name="source_notes" maxlength="5000" rows="5" required>{{ old('source_notes', $revision->source_notes) }}</textarea>
                <p class="field-hint">Use docs/tower-content-guide.md to check accuracy, attribution, licenses and a working solution before publication.</p>
            </fieldset>
        </div>
        <fieldset id="tower-config">
            <legend>Playable stages</legend>
            <p>Each level supports 1–4 stages. Boss levels require at least two. Use existing typed games or 1–10-question quizzes. Stages unlock in order.</p>
            <div class="tower-template-picks">
                @foreach(config('learning.instances') + config('arcade') + ['quiz' => config('learning.quizzes.sequences')] as $template)
                    <button type="button" class="button button-secondary button-small" data-tower-template="{{ json_encode($template, JSON_THROW_ON_ERROR) }}">+ {{ $template['title'] }}</button>
                @endforeach
            </div>
            <div data-tower-stage-summary></div>
            <details class="tower-advanced">
                <summary>Advanced · typed stage configuration</summary>
                <label for="tower-json">Stage configuration (typed JSON)</label>
                <textarea id="tower-json" name="stages_json" rows="18" maxlength="30000" required>{{ old('stages_json', json_encode($revision->stages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) }}</textarea>
                <p class="field-hint">Add a template, then edit its plain-text prompts and data. This accepts scenarios, never executable code. Garden coordinates are zero-based; other game positions start at one.</p>
            </details>
            <template data-tower-garden-shell>
                @include('learning.game', ['game' => config('learning.instances.sequences'), 'module' => null, 'completionUrl' => null, 'designerShell' => true, 'trial' => false])
            </template>
            <template data-tower-arcade-shell>
                @include('learning.arcade-game', ['game' => config('arcade.pixel-studio'), 'completionUrl' => null])
            </template>
        </fieldset>
        <div class="studio-actions">
            <button class="button button-play" type="submit">Publish level revision</button>
            <a class="button button-secondary" href="{{ route('tower-studio.index') }}">Back to tower studio</a>
        </div>
    </form>
    <details id="tower-preview" class="studio-preview">
        <summary>Try the saved revision</summary>
        <p>Preview wins stay local. Use each stage’s unsaved preview to test new edits before publishing.</p>
        @foreach($revision->stages as $instance)
            @if($instance['template'] === 'choice-quiz')
                @include('learning.quiz', ['quiz' => $instance, 'module' => null, 'completionUrl' => null])
            @else
                @include('learning.activity', ['game' => $instance, 'module' => null, 'completionUrl' => null, 'trial' => false])
            @endif
        @endforeach
    </details>
</section>
@endsection