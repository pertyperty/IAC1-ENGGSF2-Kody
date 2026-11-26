@extends('layouts.learning')
@section('title', 'Review courses — Kody')
@section('content')
<section class="review-page page-width">
@include('layouts.review-nav')
<h1>Courses awaiting review</h1><p>Check the course details, module order, and each pinned module version before publishing.</p><x-list-filters :filters="$filters"/><div class="review-list">@forelse($revisions as $revision)<a href="{{ route('course-reviews.show', $revision->course) }}"><b>{{ $revision->title }}</b><span>Revision {{ $revision->number }}</span></a>@empty<x-empty-state title="No courses are waiting for review." description="Submitted course revisions will appear here with their pinned module order."/>@endforelse</div>{{ $revisions->links() }}</section>
@endsection
