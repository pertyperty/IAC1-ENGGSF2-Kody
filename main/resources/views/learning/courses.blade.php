@extends('layouts.learning')
@section('title', 'Courses — Kody')
@section('content')
<section class="module-section page-width">
    <x-catalog-header title="Courses" description="Work through a series of modules on one topic.">
        <a class="quiet-link" href="{{ route('learning.catalog') }}">Modules</a>
        @can('viewLearning', \App\Models\LearningCourse::class)
            <a class="quiet-link" href="{{ route('course-learning.mine') }}">My library</a>
        @endcan
    </x-catalog-header>
    <form method="get" action="{{ route('course-learning.catalog') }}" class="catalog-search catalog-filter-panel">
        <div class="filter-grid">
            <div>
                <label for="course-search">Search courses</label>
                <input id="course-search" name="q" type="search" maxlength="80" value="{{ $query }}" placeholder="Search by title or topic">
            </div>
            <div>
                <label for="course-difficulty">Difficulty</label>
                <select id="course-difficulty" name="difficulty">
                    <option value="">All difficulties</option>
                    @foreach(['Beginner', 'Intermediate', 'Advanced'] as $difficulty)
                        <option value="{{ $difficulty }}" @selected(($filters['difficulty'] ?? null) === $difficulty)>{{ $difficulty }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <button class="button button-dark button-small" type="submit">Search</button>
            @if($query !== '' || ($filters['difficulty'] ?? null))
                <a class="quiet-link" href="{{ route('course-learning.catalog') }}">Clear filters</a>
            @endif
            <span class="filter-summary">{{ $courses->total() }} {{ $courses->total() === 1 ? 'course' : 'courses' }} found</span>
        </div>
    </form>
    <div class="catalog-results">
        @forelse($courses as $revision)
            @php
                $canReview = auth()->user()?->can('viewAny', \App\Models\LearningCourse::class);
                $courseTags = [
                    $revision->category,
                    $revision->difficulty,
                    trans_choice(':count hour|:count hours', $revision->estimated_duration),
                ];
                if (($revision->modules_count ?? 0) > 0) {
                    $courseTags[] = trans_choice(':count module|:count modules', $revision->modules_count);
                }
            @endphp
            <x-catalog-entry :title="$revision->title" :url="route($canReview ? 'course-reviews.show' : 'course-learning.show', $revision->course_id)"
                :action="$canReview ? 'Review this course' : (auth()->check() ? 'View course' : 'Sign in to explore')" icon="journey"
                :tags="$courseTags"
                :bookmarked="$bookmarkedCourses->has($revision->course_id)"
                bookmark-kind="course"
                :bookmark-content="$revision->course_id">
                <p class="catalog-entry-desc">{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p>
                <x-creator-byline :creator="$revision->course->creator"/>
                @if($participant)
                    @if($courseProgress->has($revision->course_id))
                        @include('learning.saved-progress', ['saved' => $courseProgress->get($revision->course_id)])
                        @if($courseProgress->get($revision->course_id)['edition'] !== $revision->id)
                            <p class="field-hint">Progress in your enrolled edition</p>
                        @endif
                    @else
                        @include('learning.saved-progress', ['saved' => ['state' => 'new', 'label' => 'Not joined']])
                    @endif
                @endif
                <p class="content-price">
                    {{ $revision->course->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}
                    @if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock @endif
                </p>
            </x-catalog-entry>
        @empty
            <x-empty-state title="No courses found" description="Try another search or browse individual modules." :action-url="route('learning.catalog')" action-label="Browse modules" />
        @endforelse
    </div>
    {{ $courses->links() }}
</section>
@endsection
