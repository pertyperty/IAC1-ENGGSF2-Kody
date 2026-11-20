@extends('layouts.learning')
@section('title', 'Challenge studio — Kody')
@section('content')

<section class="review-page page-width">
<div class="studio-heading"><div><p class="overline">CHALLENGE STUDIO</p><h1>Coding challenges</h1><p>Write a problem, add test cases, then submit it for review.</p></div><a class="button button-play" href="{{ route('challenges.create') }}">Create challenge</a></div>
<nav class="studio-wayfinding" aria-label="Challenge tools"><a href="{{ route('challenges.catalog') }}">Explore published challenges</a>@can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}">Your lessons</a>@endcan</nav>
<section class="studio-library" aria-labelledby="quest-library-heading"><div class="section-heading"><h2 id="quest-library-heading">Your challenges</h2><span class="status-pill">{{ $challenges->total() }} {{ $challenges->total() === 1 ? 'challenge' : 'challenges' }}</span></div><x-list-filters :filters="$filters" :statuses="['Draft', 'Published', 'Archived', 'Deleted']"/>
<div class="review-list">@forelse($challenges as $challenge)<a href="{{ route('challenges.edit', $challenge) }}"><b>{{ $challenge->latestRevision->title }}</b><span>{{ $challenge->status }} · Revision {{ $challenge->latestRevision->number }} · {{ $challenge->latestRevision->review_status }}</span><span class="quiet-link">Open draft</span></a>@empty<div class="empty-state"><h3>No coding challenges yet.</h3><p>Create a challenge or start with a beginner example.</p></div>@endforelse</div>{{ $challenges->links() }}</section>
<details class="starter-library"><summary>Use a beginner example</summary><p>Each example includes sample and hidden test cases. Edit it before saving.</p><div class="starter-grid">@foreach(config('challenge-examples') as $slug => $example)<article class="starter-card"><h3>{{ $example['title'] }}</h3>@foreach(config('challenges.languages') as $language => $label)<p><a class="quiet-link" href="{{ route('challenges.create', ['example' => $slug, 'language' => $language]) }}">Use {{ $label }} example</a></p>@endforeach</article>@endforeach</div></details>
</section>
@endsection
