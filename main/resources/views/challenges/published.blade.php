@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="page-width quest-page challenge-workspace">
    <a class="quiet-link" href="{{ route('challenges.catalog') }}"><x-action-icon name="left"/> Coding challenges</a>
    <header class="challenge-hero">
        <div>
            <p class="overline"><x-action-icon name="code"/> Coding practice</p>
            <h1>{{ $revision->title }}</h1>
            <x-creator-byline :creator="$challenge->creator"/>
        </div>
        @if($attempts->isNotEmpty())
            <a class="button button-secondary" href="#challenge-history">Your attempts</a>
        @endif
    </header>
    <a class="quiet-link challenge-workspace-jump" href="#solution">Go to editor</a>
    <div class="quest-play-columns">
        <div data-quest-problem role="region" aria-label="Problem and examples" tabindex="0">
            @include('challenges.problem')
            @include('challenges.starter-code')
            @include('challenges.samples')
        </div>
        <div class="challenge-solution-panel">
            @include('challenges.participation', ['attemptAction' => route('challenge-attempts.store', $challenge), 'attemptScope' => 'this challenge'])
            @include('engagement.reactions')
        </div>
    </div>
</section>
@endsection
