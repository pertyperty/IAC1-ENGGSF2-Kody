@extends('layouts.learning')
@section('title', 'Creator studio — Kody')
@section('content')

<section class="review-page page-width">
    <div class="studio-heading"><div><p class="overline">INSTRUCTOR WORKSPACE</p><h1>Your creator studio.</h1><p>Turn an idea into a little adventure. Add a lesson, choose a game or quiz, and send it for review.</p></div><a class="button button-play" href="{{ route('studio.create') }}">Create an adventure +</a></div>
    <nav class="studio-wayfinding" aria-label="Creator tools"><a href="{{ route('courses.index') }}">≡ Course builder</a><a href="{{ route('challenges.index') }}">{ } Coding quests</a><a href="{{ route('curriculum.index') }}">✦ Beginner course plans</a></nav>
    <section class="studio-library" aria-labelledby="module-library-heading"><div class="section-heading"><h2 id="module-library-heading">Your adventures</h2><span class="status-pill">{{ $modules->total() }} {{ $modules->total() === 1 ? 'adventure' : 'adventures' }}</span></div>
    <div class="review-list">@forelse($modules as $module)<a href="{{ route('studio.edit', $module) }}"><b>{{ $module->latestRevision->title }}</b><span>{{ $module->status }} · Revision {{ $module->latestRevision->number }} · {{ $module->latestRevision->review_status }}</span><span class="quiet-link">Open draft →</span></a>@empty<div class="empty-state"><h3>Your first adventure starts with one idea.</h3><p>Choose a ready-to-play example below or create a blank adventure. Your work stays a draft until you submit it for review.</p></div>@endforelse</div>{{ $modules->links() }}</section>
    <details class="starter-library"><summary>Start with a ready-to-play idea.</summary><p>Each example includes a short lesson, prompts and an assessment. Make it yours, preview it, then save a draft for review.</p><div class="starter-grid">@foreach(config('creator-examples') as $slug => $example)<a class="starter-card" href="{{ route('studio.create', ['example' => $slug]) }}"><h3>{{ $example['title'] }}</h3><p>{{ $example['description'] }}</p><span>Use this example →</span></a>@endforeach</div></details>
</section>
@endsection
