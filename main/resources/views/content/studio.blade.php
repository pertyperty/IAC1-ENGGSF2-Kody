@extends('layouts.learning')
@section('title', 'Creator studio — Kody')
@section('content')
@if(session('status'))<p class="lesson-note page-width" role="status">{{ session('status') }}</p>@endif
<section class="review-page page-width">
    <p class="overline">MAKE SOMEONE’S NEXT AHA MOMENT</p><h1>Your creator studio.</h1>
    <p>Turn an idea into a little adventure. Add a lesson, choose a game or quiz, and send it for review.</p>
    <a class="button button-play" href="{{ route('studio.create') }}">Create an adventure +</a>
    <p><a class="quiet-link" href="{{ route('curriculum.index') }}">Explore complete beginner course plans →</a></p>
    <section class="starter-examples" aria-labelledby="starter-heading"><h2 id="starter-heading">Start with a ready-to-play idea.</h2><p>Each example includes a short lesson, prompts and an assessment. Make it yours, preview it, then save a draft for review.</p><div class="starter-grid">@foreach(config('creator-examples') as $slug => $example)<a class="starter-card" href="{{ route('studio.create', ['example' => $slug]) }}"><h3>{{ $example['title'] }}</h3><p>{{ $example['description'] }}</p><span>Use this example →</span></a>@endforeach</div></section>
    <p><a class="quiet-link" href="{{ route('courses.index') }}">Build a course from your adventures →</a></p>
    <p><a class="quiet-link" href="{{ route('challenges.index') }}">Make a coding quest →</a></p>
    <div class="review-list">@forelse($modules as $module)<a href="{{ route('studio.edit', $module) }}"><b>{{ $module->latestRevision->title }}</b><span>{{ $module->status }} · Revision {{ $module->latestRevision->number }} · {{ $module->latestRevision->review_status }}</span></a>@empty<p class="lesson-note">Your first adventure starts with one idea. What would you love to help someone understand?</p>@endforelse</div>
    {{ $modules->links() }}
</section>
@endsection
