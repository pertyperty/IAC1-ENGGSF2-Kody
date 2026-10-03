@extends('layouts.learning')
@section('title', 'Review adventures — Kody')
@section('content')
<section class="review-page page-width"><p><a class="quiet-link" href="{{ route('challenge-reviews.index') }}">Review coding quests</a></p><h1>Adventures ready for a look.</h1><p>Check the lesson and try its activity before approving publication. The creator’s previous approved revision stays live during review.</p>
    <p><a class="quiet-link" href="{{ route('course-reviews.index') }}">Review bigger journeys →</a></p>
    <div class="review-list">@forelse($revisions as $revision)<a href="{{ route('module-reviews.show', $revision->module) }}"><b>{{ $revision->title }}</b><span>Revision {{ $revision->number }} · {{ $revision->type }}</span></a>@empty<p>No adventures are waiting for review.</p>@endforelse</div>{{ $revisions->links() }}
</section>
@endsection
