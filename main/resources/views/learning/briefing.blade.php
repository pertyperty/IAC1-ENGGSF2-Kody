@php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
<section class="lesson-briefing" aria-label="Activity briefing">
    <div class="activity-goal"><b>Goal</b><p tabindex="0">{{ $briefing['task'] }}</p></div>
    <details class="activity-reference"><summary>How it works</summary><div tabindex="0" role="region" aria-label="Activity explanation"><p>{{ $briefing['explanation'] }}</p><pre>{{ $briefing['example'] }}</pre><p>{{ $briefing['success'] }}</p></div></details>
</section>
