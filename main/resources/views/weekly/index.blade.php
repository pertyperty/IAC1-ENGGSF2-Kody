@extends('layouts.learning')
@section('title', 'Weekly quests — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('dashboard') }}">← Your playground</a><p class="overline">ONE WEEK. A FRESH POSSIBILITY.</p><h1>Try it together.</h1>
<p>A new coding quest each Sunday at midnight in Manila. Each week brings three fresh attempts.</p>
@if($current)<article class="lesson-note"><span class="module-meta">{{ config('challenges.languages')[$current->revision->language] }} · {{ $current->revision->difficulty }}</span><h2>{{ $current->revision->title }}</h2><a class="button button-play" href="{{ route('weekly-events.show', $current) }}">Explore this week's quest →</a></article>@else<p class="lesson-note">No weekly quest is open yet. Your game ladder is ready whenever you are.</p>@endif
<p><a class="quiet-link" href="{{ route('leaderboards.index') }}">Learning ladder and published weekly results →</a></p><h2>Your weekly attempts</h2>@forelse($attempts as $attempt)<article class="lesson-note"><p>Week of {{ \Carbon\CarbonImmutable::parse($attempt->starts_at)->setTimezone('Asia/Manila')->format('M j, Y') }}</p><h3>{{ $attempt->title }}</h3><a class="quiet-link" href="{{ route('challenge-attempts.show', $attempt->id) }}">Attempt {{ $attempt->attempt }} · {{ $attempt->status }} →</a></article>@empty<p>Your weekly adventure starts with your first confirmed solution.</p>@endforelse{{ $attempts->links() }}</section>
@endsection
