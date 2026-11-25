@extends('layouts.learning')
@section('title', 'Your attempt | Kody')
@section('content')
<section class="review-page page-width challenge-result">
    <a class="quiet-link" href="{{ $weeklyEvent ? route('weekly-events.index') : route('challenges.catalog') }}"><x-action-icon name="left"/> {{ $weeklyEvent ? 'Weekly quests' : 'Coding challenges' }}</a>
    <p class="eyebrow">Attempt {{ $submission->attempt }} of 3 | Revision {{ $revision->number }}</p>
    <h1>{{ $revision->title }}</h1>
    @if($weeklyEvent)<p>Weekly quest | Week of {{ $weeklyEvent->starts_at->setTimezone('Asia/Manila')->format('M j, Y') }}</p>@endif
    <div class="challenge-result-summary" @if(!$submission->completed_at) data-challenge-status="{{ route('challenge-attempts.status', $submission->id) }}" @endif>
        <div role="status" aria-live="polite">
            <h2 data-attempt-status>{{ $submission->status }}</h2>
            <p data-attempt-feedback>{{ $summary['issues'] !== [] && $submission->status === 'Failed' ? 'Review the results below before trying again.' : ($submission->feedback ?? 'Your code is saved. You can leave while we check it.') }}</p>
            <p data-attempt-counts>@if($submission->completed_at){{ $submission->passed_cases }} / {{ $submission->total_cases }} test cases passed.@else {{ $summary['evaluated_cases'] }} / {{ $submission->total_cases }} test cases evaluated.@endif</p>
            @if(!$submission->completed_at)<progress data-attempt-progress max="{{ max(1, $submission->total_cases) }}" value="{{ $summary['evaluated_cases'] }}" aria-label="Code evaluation progress"></progress>@endif
        </div>
        <ul class="challenge-result-issues" data-attempt-issues @if($summary['issues'] === []) hidden @endif>
            @foreach($summary['issues'] as $issue)
                <li><strong>{{ $issue['label'] }} ({{ $issue['count'] }})</strong><p>{{ $issue['hint'] }}</p></li>
            @endforeach
        </ul>
        @include('challenges.result-details')
        @if(!$submission->completed_at)<a data-attempt-refresh class="button button-secondary" href="{{ route('challenge-attempts.show', $submission->id) }}">Refresh result</a>@endif
        @if($canRetry)<a data-attempt-retry class="button button-play" href="{{ $retryUrl }}" @if(!in_array($submission->status, ['Failed', 'Unavailable'], true)) hidden @endif>Edit saved solution</a>@endif
    </div>
    <h2>Your code</h2>
    <pre class="challenge-case" tabindex="0"><code>{{ $submission->source_code }}</code></pre>
</section>
@endsection
