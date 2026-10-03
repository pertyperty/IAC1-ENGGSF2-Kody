@extends('layouts.learning')
@section('title', 'This week’s quest — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('weekly-events.index') }}">← Weekly quests</a><p class="overline">THIS WEEK'S SHARED CHALLENGE</p><h1>{{ $revision->title }}</h1>
<p>Closes {{ $weeklyEvent->ends_at->setTimezone('Asia/Manila')->format('M j, Y · g:i a') }} Manila time. Three fresh attempts for this event.</p>
<div class="lesson-note"><h2>The weekly rules</h2><p class="lesson-text">{{ $weeklyEvent->rules }}</p><p>Free play and verified results. XP, ranks and KodeBit rewards are not awarded in this release.</p></div>
@include('challenges.problem')<h2>A few examples</h2>@forelse($samples as $sample)<div class="lesson-note challenge-case"><h3>Sample {{ $loop->iteration }}</h3><p>Input</p><pre>{{ $sample->input }}</pre><p>Expected output</p><pre>{{ $sample->expected_output }}</pre></div>@empty<p>This quest has no public samples. Follow the formats above.</p>@endforelse
@include('challenges.participation')@include('engagement.reactions')</section>
@endsection
