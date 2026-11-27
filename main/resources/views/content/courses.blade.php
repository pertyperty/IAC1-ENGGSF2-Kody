@extends('layouts.learning')
@section('title', 'Course builder — Kody')
@section('content')
<section class="review-page page-width">
    <div class="studio-heading">
        <div>
            <p class="overline">INSTRUCTOR WORKSPACE</p>
            <h1>Your courses</h1>
            <p>Organize your published modules into structured courses.</p>
        </div>
        <a class="button button-play" href="{{ route('courses.create') }}"><x-action-icon name="plus"/> Create course</a>
    </div>
    <nav class="studio-wayfinding" aria-label="Creator tools">
        <a href="{{ route('studio.index') }}"><x-action-icon name="module"/> Modules</a>
        <a href="{{ route('courses.index') }}" aria-current="page"><x-action-icon name="course"/> Course builder</a>
        <a href="{{ route('challenges.index') }}"><x-action-icon name="code"/> Coding challenges</a>
        <a href="{{ route('curriculum.index') }}"><x-action-icon name="spark"/> Beginner course plans</a>
        <a href="{{ route('creators.show', auth()->user()) }}"><x-action-icon name="profile"/> Public profile</a>
        <a href="{{ route('earnings.index') }}"><x-action-icon name="wallet"/> Earnings</a>
    </nav>
    <section class="studio-library" aria-labelledby="course-library-heading">
        <div class="section-heading">
            <h2 id="course-library-heading">Course library</h2>
            <span class="status-pill">{{ $courses->total() }} {{ $courses->total() === 1 ? 'course' : 'courses' }}</span>
        </div>
        <x-list-filters :filters="$filters" :statuses="['Draft', 'Published', 'Archived', 'Deleted']"/>
        <div class="review-list">
            @forelse($courses as $course)
                <a href="{{ route('courses.edit', $course) }}">
                    <b>{{ $course->latestRevision->title }}</b>
                    <span>{{ $course->status }} · Revision {{ $course->latestRevision->number }} · {{ $course->latestRevision->review_status }}</span>
                    <span class="quiet-link">Open course</span>
                </a>
            @empty
                <x-empty-state title="No courses yet." description="Create a course and add your published modules, or start from a beginner course plan." :action-url="route('curriculum.index')" action-label="Browse beginner course plans"/>
            @endforelse
        </div>
        {{ $courses->links() }}
    </section>
</section>
@endsection
