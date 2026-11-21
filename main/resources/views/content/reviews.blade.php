@extends('layouts.learning')
@section('title', 'Review modules — Kody')
@section('content')
<section class="review-page page-width">
@include('layouts.review-nav')
<h1>Modules awaiting review</h1><p>Check the explanation and assessment before publishing. The previous approved revision stays available during review.</p>
    <x-list-filters :filters="$filters"/><div class="review-list">@forelse($revisions as $revision)<a href="{{ route('module-reviews.show', $revision->module) }}"><b>{{ $revision->title }}</b><span>Revision {{ $revision->number }} · {{ $revision->type }}</span></a>@empty<x-empty-state title="No modules awaiting review." description="New submissions will appear here. Use the publication queues above to check courses or coding challenges."/>@endforelse</div>{{ $revisions->links() }}
</section>
@endsection
