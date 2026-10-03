@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('challenges.catalog') }}">← Coding quests</a><h1>{{ $revision->title }}</h1>@include('challenges.problem')
<h2>A few examples</h2>@forelse($samples as $sample)<div class="lesson-note challenge-case"><h3>Sample {{ $loop->iteration }}</h3><p>Input</p><pre>{{ $sample->input }}</pre><p>Expected output</p><pre>{{ $sample->expected_output }}</pre></div>@empty<p>This challenge has no public samples. Follow the input and output formats above.</p>@endforelse
@include('challenges.participation', ['attemptAction' => route('challenge-attempts.store', $challenge), 'attemptScope' => 'this quest across all its published revisions'])@include('engagement.reactions')</section>
@endsection
