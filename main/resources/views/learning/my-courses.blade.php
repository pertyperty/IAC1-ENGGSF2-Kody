@extends('layouts.learning')
@section('title', 'Your journeys — Kody')
@section('content')
<section class="review-page page-width"><h1>Keep your curiosity going.</h1><p>Your learning journeys are waiting where you left them.</p><a class="button button-play" href="{{ route('course-learning.catalog') }}">Find a new journey →</a><div class="review-list">@forelse($enrollments as $enrollment)<a href="{{ route('course-learning.show', $enrollment->course_id) }}"><b>{{ $enrollment->revision->title }}</b><span>{{ $enrollment->revision->difficulty }} @if($enrollment->course->status === 'Archived') · Archived · Your access is retained @endif</span></a>@empty<p>No journeys yet. Choose a course and take your first step.</p>@endforelse</div>{{ $enrollments->links() }}</section>
@endsection
