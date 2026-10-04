@extends('layouts.learning')
@section('title', 'Creator courses — Kody')
@section('content')
@if(session('status'))<p class="lesson-note page-width" role="status">{{ session('status') }}</p>@endif
<section class="review-page page-width"><a class="quiet-link" href="{{ route('studio.index') }}">← Your adventures</a><h1>Build a bigger journey.</h1><p>Bring little adventures together into a course.</p><a class="button button-play" href="{{ route('courses.create') }}">Create a course +</a>
    <div class="review-list">@forelse($courses as $course)<a href="{{ route('courses.edit', $course) }}"><b>{{ $course->latestRevision->title }}</b><span>{{ $course->status }} · Revision {{ $course->latestRevision->number }} · {{ $course->latestRevision->review_status }}</span></a>@empty<p>No course drafts yet. Start with a journey you would love to teach.</p>@endforelse</div>{{ $courses->links() }}
</section>
@endsection
