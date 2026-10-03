@extends('layouts.learning')
@section('title', 'Your attempt — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('challenges.catalog') }}">← Coding quests</a>
<p class="eyebrow">Attempt {{ $submission->attempt }} of 3 · Revision {{ $revision->number }}</p><h1>{{ $revision->title }}</h1>
<div class="lesson-note" role="status" aria-live="polite" @if(!$submission->completed_at) data-challenge-status="{{ route('challenge-attempts.status', $submission->id) }}" @endif><h2 data-attempt-status>{{ $submission->status }}</h2>
<p data-attempt-feedback>{{ $submission->feedback ?? 'Your code is saved. Evaluation continues if you close this page.' }}</p>
<p data-attempt-counts>@if($submission->completed_at){{ $submission->passed_cases }} / {{ $submission->total_cases }} test cases passed.@endif</p>
@if(!$submission->completed_at)<a class="button button-play" href="{{ route('challenge-attempts.show', $submission->id) }}">Refresh result →</a>@endif</div>
<h2>Your code</h2><pre class="challenge-case">{{ $submission->source_code }}</pre>
<a class="quiet-link" href="{{ route('challenges.show', $submission->challenge_id) }}">Return to quest</a></section>
@endsection
