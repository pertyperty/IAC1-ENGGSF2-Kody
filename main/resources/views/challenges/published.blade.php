@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="page-width quest-page challenge-workspace">
    <a class="quiet-link" href="{{ route('challenges.catalog') }}"><x-action-icon name="left"/> Coding challenges</a>
    <h1>{{ $revision->title }}</h1>
    <x-creator-byline :creator="$challenge->creator"/>
    <a class="quiet-link challenge-workspace-jump" href="#solution">Go to editor</a>
    <div class="quest-play-columns">
        <div data-quest-problem role="region" aria-label="Problem and examples" tabindex="0">
            @include('challenges.problem')
            @include('challenges.starter-code')
            <h2>A few examples</h2>
            @forelse($samples as $sample)
                <div class="lesson-note challenge-case">
                    <h3>Sample {{ $loop->iteration }}</h3>
                    <p>Input</p>
                    <pre>{{ $sample->input }}</pre>
                    <p>Expected output</p>
                    <pre>{{ $sample->expected_output }}</pre>
                </div>
            @empty
                <p>This challenge has no public samples. Follow the input and output formats above.</p>
            @endforelse
        </div>
        <div class="challenge-solution-panel">
            @include('challenges.participation', ['attemptAction' => route('challenge-attempts.store', $challenge), 'attemptScope' => 'this challenge'])
            @include('engagement.reactions')
        </div>
    </div>
</section>
@endsection
