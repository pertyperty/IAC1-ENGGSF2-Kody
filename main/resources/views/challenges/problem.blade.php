<p class="overline">{{ config('challenges.languages.'.$revision->language) }} · {{ $revision->difficulty }}</p>
<p>{{ config('challenges.categories.'.$revision->category) }}@foreach($revision->tags ?? [] as $tag) · {{ config('challenges.tags.'.$tag) }}@endforeach</p>
<div class="challenge-brief">
    <h2>The problem</h2>
    <x-learning-prose :source="$revision->description" class="challenge-prose" />
    <h2>Rules and constraints</h2>
    <x-learning-prose :source="$revision->rules" class="challenge-prose" />
    <h2>Input</h2>
    <x-learning-prose :source="$revision->input_format" class="challenge-prose" />
    <h2>Output</h2>
    <x-learning-prose :source="$revision->output_format" class="challenge-prose" />
    <p class="field-hint">CPU time per test: {{ $revision->cpu_time_ms }} ms · Memory per test: {{ $revision->memory_kib }} KiB</p>
</div>

<details class="challenge-hints">
    <summary>Hints for testing your code</summary>
    <p>Start with the smallest public example. Match the expected output exactly and leave out prompts or debug text.</p>
    @if($revision->language === 'java')
        <p>Use a class named <code>Main</code> with <code>public static void main(String[] args)</code>. Leave out a package declaration.</p>
    @endif
</details>
