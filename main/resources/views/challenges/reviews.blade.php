@extends('layouts.learning')
@section('title', 'Review coding challenges — Kody')
@section('content')
<section class="review-page page-width">
@include('layouts.review-nav')
<h1>Coding challenges awaiting review</h1><p>Review the problem statement, execution settings, and every sample and hidden test case before publishing.</p><x-list-filters :filters="$filters"/><div class="review-list">@forelse($revisions as $revision)<a href="{{ route('challenge-reviews.show', $revision->challenge) }}"><b>{{ $revision->title }}</b><span>{{ config('challenges.languages.'.$revision->language) }} · Revision {{ $revision->number }}</span></a>@empty<x-empty-state title="No challenges are waiting for review." description="Submitted coding challenges will appear here for problem and test-case review."/>@endforelse</div>{{ $revisions->links() }}</section>
@endsection
