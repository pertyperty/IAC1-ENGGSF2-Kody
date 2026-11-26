@extends('layouts.learning')
@section('title', 'Submission result | Kody')
@section('content')
@php
    $complete = $submission->completed_at !== null;
    $graded = in_array($submission->status, ['Passed', 'Failed'], true);
    $verdict = strtolower($submission->status);
@endphp
<section class="review-page page-width challenge-result">
    <a class="quiet-link" href="{{ $weeklyEvent ? route('weekly-events.index') : route('challenges.catalog') }}"><x-action-icon name="left"/> {{ $weeklyEvent ? 'Weekly quests' : 'Coding challenges' }}</a>
    <header class="challenge-hero">
        <div>
            <p class="overline">Submission result</p>
            <h1>{{ $revision->title }}</h1>
            <p>Attempt {{ $submission->attempt }} of 3 <span aria-hidden="true">/</span> Revision {{ $revision->number }}</p>
            @if($weeklyEvent)<p>Weekly quest: week of {{ $weeklyEvent->starts_at->setTimezone('Asia/Manila')->format('M j, Y') }}</p>@endif
        </div>
    </header>
    <div class="challenge-result-grid">
        <section class="challenge-result-summary" data-verdict="{{ $verdict }}" aria-labelledby="submission-verdict" @if(!$complete) data-challenge-status="{{ route('challenge-attempts.status', $submission->id) }}" @endif>
            <div class="challenge-result-lead" role="status" aria-live="polite">
                <h2 id="submission-verdict" class="challenge-verdict" data-verdict="{{ $verdict }}" data-attempt-status>{{ $submission->status }}</h2>
                <p data-attempt-feedback>{{ $summary['issues'] !== [] && $submission->status === 'Failed' ? 'Review the results below before trying again.' : ($submission->feedback ?? 'Your code is saved. You can leave while we check it.') }}</p>
                @if($graded || !$complete)
                    <div class="challenge-case-progress">
                        <strong data-attempt-counts>@if($complete){{ $submission->passed_cases }} / {{ $submission->total_cases }} test cases passed.@else{{ $summary['evaluated_cases'] }} / {{ $submission->total_cases }} test cases evaluated.@endif</strong>
                        <progress @if(!$complete) data-attempt-progress @endif max="{{ max(1, $submission->total_cases) }}" value="{{ $complete ? $submission->passed_cases : $summary['evaluated_cases'] }}" aria-label="{{ $complete ? 'Test cases passed' : 'Code evaluation progress' }}"></progress>
                    </div>
                @endif
            </div>
            @if(!$complete)<a data-attempt-refresh class="button button-secondary" href="{{ route('challenge-attempts.show', $submission->id) }}">Refresh result</a>@endif
            @if($canRetry && in_array($submission->status, ['Failed', 'Unavailable'], true))<a data-attempt-retry class="button button-play" href="{{ $retryUrl }}">Edit saved solution</a>@endif
            @if($summary['issues'] !== [])
                <div class="challenge-result-detail">
                    <h3>What to check</h3>
                    <ul class="challenge-result-issues" data-attempt-issues>
                        @foreach($summary['issues'] as $issue)
                            <li><strong>{{ $issue['label'] }} ({{ $issue['count'] }})</strong><p>{{ $issue['hint'] }}</p></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @include('challenges.result-details')
        </section>
        <section class="challenge-submitted-code" aria-labelledby="submitted-code-heading">
            <div class="challenge-pane-heading">
                <h2 id="submitted-code-heading">Your code</h2>
                <span class="catalog-tag">{{ config('challenges.languages.'.$revision->language) }}</span>
            </div>
            <pre tabindex="0" aria-label="Submitted code"><code>{{ $submission->source_code }}</code></pre>
        </section>
    </div>
</section>
@endsection
