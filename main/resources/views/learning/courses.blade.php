@extends('layouts.learning')
@section('title', 'Courses — Kody')
@section('content')
<section class="module-section page-width">
    <p class="overline">COURSES</p>
    <h1>Find a course</h1>
    <p>Learn with a sequence of modules from a creator.</p>
    <div class="studio-actions">
        <a class="button button-secondary button-small" href="{{ route('learning.catalog') }}"><x-action-icon name="book"/> Browse modules</a>
        @can('viewLearning', \App\Models\LearningCourse::class)
            <a class="button button-secondary button-small" href="{{ route('course-learning.mine') }}"><x-action-icon name="journey"/> Your courses</a>
        @endcan
    </div>
    <form method="get" action="{{ route('course-learning.catalog') }}" class="catalog-search catalog-filter-panel">
        <div class="filter-grid">
            <div>
                <label for="course-search">Find a course</label>
                <input id="course-search" name="q" type="search" maxlength="80" value="{{ $query }}" placeholder="Search ideas, titles or categories">
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
    <div class="module-grid">
        @forelse($courses as $revision)
            <article class="module-card mint">

                <div class="module-copy">
                    <div class="module-meta"><span>{{ $revision->category }}</span><span>{{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours</span></div>
                    <h2><a href="{{ route(auth()->user()?->can('viewAny', \App\Models\LearningCourse::class) ? 'course-reviews.show' : 'course-learning.show', $revision->course_id) }}">{{ $revision->title }}</a></h2><x-creator-byline :creator="$revision->course->creator"/>
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
                    <p>{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p>
                    <a class="module-link" href="{{ route(auth()->user()?->can('viewAny', \App\Models\LearningCourse::class) ? 'course-reviews.show' : 'course-learning.show', $revision->course_id) }}">
                        @can('viewAny', \App\Models\LearningCourse::class)
                            Review this course
                        @else
                            @auth View course @else Sign in to explore @endauth
                        @endcan

                    </a>
                </div>
            </article>
        @empty
            <x-empty-state title="No courses found" description="Try another search or browse individual modules." :action-url="route('learning.catalog')" action-label="Browse modules" />
        @endforelse
    </div>
    {{ $courses->links() }}
</section>
@endsection
