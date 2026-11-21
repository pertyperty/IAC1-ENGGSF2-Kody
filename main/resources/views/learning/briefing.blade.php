@php($briefing = app(\App\Services\Games\LessonBriefings::class)->for($game))
<section class="lesson-briefing" aria-label="Activity briefing">
    <div class="activity-goal"><b>Goal</b><p tabindex="0">{{ $briefing['task'] }}</p></div>
</section>
