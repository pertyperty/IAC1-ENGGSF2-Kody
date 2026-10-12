@extends('layouts.learning')
@section('title', 'Review adventures — Kody')
@section('content')
<section class="review-page page-width">
@include('layouts.review-nav')
<h1>Adventures ready for a look.</h1><p>Check the lesson and try its activity before approving publication. The creator’s previous approved revision stays live during review.</p>
    <div class="review-list">@forelse($revisions as $revision)<a href="{{ route('module-reviews.show', $revision->module) }}"><b>{{ $revision->title }}</b><span>Revision {{ $revision->number }} · {{ $revision->type }}</span></a>@empty<x-empty-state title="No adventures are waiting for review." description="New submissions will appear here. Use the publication queues above to check courses or coding quests."/>@endforelse</div>{{ $revisions->links() }}
</section>
@endsection
