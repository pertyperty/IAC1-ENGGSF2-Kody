@extends('layouts.learning')
@section('title', 'Creator courses — Kody')
@section('content')

<section class="review-page page-width"><a class="quiet-link" href="{{ route('studio.index') }}"><x-action-icon name="left"/> Your lessons</a><h1>Your courses</h1><p>Organize your published lessons into courses.</p><a class="button button-play" href="{{ route('courses.create') }}">Create course</a>
    <div class="review-list">@forelse($courses as $course)<a href="{{ route('courses.edit', $course) }}"><b>{{ $course->latestRevision->title }}</b><span>{{ $course->status }} · Revision {{ $course->latestRevision->number }} · {{ $course->latestRevision->review_status }}</span></a>@empty<p>No course drafts yet. Start with a journey you would love to teach.</p>@endforelse</div>{{ $courses->links() }}
</section>
@endsection
