@extends('layouts.learning')
@section('title', 'Review courses — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('module-reviews.index') }}">← Adventure reviews</a><h1>A bigger journey, ready for review.</h1><p>Check the course details, lesson order and each saved adventure.</p><div class="review-list">@forelse($revisions as $revision)<a href="{{ route('course-reviews.show', $revision->course) }}"><b>{{ $revision->title }}</b><span>Revision {{ $revision->number }}</span></a>@empty<p>No courses are waiting for review.</p>@endforelse</div>{{ $revisions->links() }}</section>
@endsection
