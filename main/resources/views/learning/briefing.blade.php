@php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
<section class="lesson-briefing" aria-label="Activity briefing">
    <div><span class="overline">YOU WILL LEARN</span><p>{{ $briefing['outcome'] }}</p></div>
    <div><b>Your task</b><p>{{ $briefing['task'] }}</p><p class="field-hint"><b>Success:</b> {{ $briefing['success'] }}</p></div>
    <details><summary>How it works · a different example</summary><p>{{ $briefing['explanation'] }}</p><pre>{{ $briefing['example'] }}</pre><p>Before stepping: predict what changes next. After stepping: compare your prediction with the state. Try a different instruction to explain the difference.</p></details>
</section>
