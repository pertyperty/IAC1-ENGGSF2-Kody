@php
    $briefing = app(\App\Services\Games\LessonBriefings::class)->for($game);
@endphp
<details class="activity-reference">
    <summary>How it works</summary>
    <div tabindex="0" role="region" aria-label="Activity explanation" class="concept-card-body">
        <div class="concept-card-head">
            <span class="learning-concept">{{ $game['concept'] ?? 'Programming concepts' }}</span>
            @if(!empty($briefing['outcome']))<p class="concept-outcome">{{ $briefing['outcome'] }}</p>@endif
        </div>
        <div class="concept-section">
            <strong>Key rule</strong>
            <p>{{ $briefing['explanation'] }}</p>
        </div>
        <div class="concept-section concept-example-box">
            <strong>Worked example</strong>
            <pre>{{ $briefing['example'] }}</pre>
        </div>
        <div class="concept-section concept-win-box">
            <strong>Win condition</strong>
            <p>{{ $briefing['success'] }}</p>
        </div>
        <a class="quiet-link" href="{{ route('learning.catalog', ['template' => $game['template']]) }}">Related modules</a>
    </div>
</details>
