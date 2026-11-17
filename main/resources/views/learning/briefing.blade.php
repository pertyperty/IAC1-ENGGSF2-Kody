@php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
<section class="lesson-briefing" aria-label="Activity briefing">
    <div><b>Your task</b><p>{{ $briefing['task'] }}</p><p class="field-hint"><b>Success:</b> {{ $briefing['success'] }}</p></div>
    <details><summary>How it works</summary><p><b>Learn:</b> {{ $briefing['outcome'] }}</p><p>{{ $briefing['explanation'] }}</p><pre>{{ $briefing['example'] }}</pre></details>
</section>
