@php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
<details class="activity-reference"><summary>How it works</summary><div tabindex="0" role="region" aria-label="Activity explanation"><p>{{ $briefing['explanation'] }}</p><pre>{{ $briefing['example'] }}</pre><p>{{ $briefing['success'] }}</p></div></details>
