<p class="overline">{{ config('challenges.languages.'.$revision->language) }} · {{ $revision->difficulty }}</p>
<p>{{ config('challenges.categories.'.$revision->category) }}@foreach($revision->tags ?? [] as $tag) · {{ config('challenges.tags.'.$tag) }}@endforeach</p>
<div class="lesson-note">
    <h2>The problem</h2>
    <x-learning-prose :source="$revision->description" class="lesson-text" />
    <h2>Rules and constraints</h2>
    <x-learning-prose :source="$revision->rules" class="lesson-text" />
    <h2>Input</h2>
    <x-learning-prose :source="$revision->input_format" class="lesson-text" />
    <h2>Output</h2>
    <x-learning-prose :source="$revision->output_format" class="lesson-text" />
    <p>CPU time per test: {{ $revision->cpu_time_ms }} ms · Memory per test: {{ $revision->memory_kib }} KiB</p>
</div>
