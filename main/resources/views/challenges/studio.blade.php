@extends('layouts.learning')
@section('title', 'Challenge studio — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">GIVE CURIOSITY A CHALLENGE</p><h1>Make a little coding quest.</h1><p>Start with a clear problem. Add examples and hidden checks, then send your quest for review.</p><a class="button button-play" href="{{ route('challenges.create') }}">Create a challenge +</a><p><a class="quiet-link" href="{{ route('challenges.catalog') }}">Explore published challenges →</a>@can('create', \App\Models\LearningModule::class) · <a class="quiet-link" href="{{ route('studio.index') }}">Your learning adventures</a>@endcan</p>
<div class="review-list">@forelse($challenges as $challenge)<a href="{{ route('challenges.edit', $challenge) }}"><b>{{ $challenge->latestRevision->title }}</b><span>{{ $challenge->status }} · Revision {{ $challenge->latestRevision->number }} · {{ $challenge->latestRevision->review_status }}</span></a>@empty<p class="lesson-note">Your first quest starts with a problem worth solving.</p>@endforelse</div>{{ $challenges->links() }}</section>
@endsection
