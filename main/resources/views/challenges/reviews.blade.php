@extends('layouts.learning')
@section('title', 'Review coding quests — Kody')
@section('content')
<section class="review-page page-width">
@include('layouts.review-nav')
<h1>Help a good quest find its learners.</h1><p>Review the problem, execution settings and every sample and hidden check.</p><p><a class="quiet-link" href="{{ route('module-reviews.index') }}">Adventure reviews</a> · <a class="quiet-link" href="{{ route('course-reviews.index') }}">Course reviews</a></p><div class="review-list">@forelse($revisions as $revision)<a href="{{ route('challenge-reviews.show', $revision->challenge) }}"><b>{{ $revision->title }}</b><span>{{ config('challenges.languages.'.$revision->language) }} · Revision {{ $revision->number }}</span></a>@empty<x-empty-state title="No challenges are waiting for review." description="Submitted coding quests will appear here for problem and test-case review."/>@endforelse</div>{{ $revisions->links() }}</section>
@endsection
