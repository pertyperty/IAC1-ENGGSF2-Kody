@extends('layouts.learning')

@section('title', 'Learning journeys — Kody')

@section('content')

<section class="module-section page-width"><p class="overline">ONE SMALL ADVENTURE AT A TIME</p><h1>Find your next journey.</h1><p>Follow a creator's trail from your first idea to your next breakthrough.</p><p><a class="quiet-link" href="{{ route('learning.catalog') }}">Explore individual adventures</a> @can('viewLearning', \App\Models\LearningCourse::class) <a class="quiet-link" href="{{ route('course-learning.mine') }}">Your journeys</a>@endcan</p>

<form method="get" class="catalog-search"><label for="course-search">Find a course</label><div><input id="course-search" name="q" type="search" maxlength="80" value="{{ $query }}" placeholder="Search ideas, titles or categories"><button class="button button-dark button-small">Search</button></div></form>

<div class="module-grid">@forelse($courses as $revision)<a class="module-card mint" href="{{ route(auth()->user()?->can('viewAny', \App\Models\LearningCourse::class) ? 'course-reviews.show' : 'course-learning.show', $revision->course_id) }}"><div class="module-art" aria-hidden="true"><span class="module-symbol">↗</span></div><div class="module-copy"><div class="module-meta"><span>{{ $revision->category }}</span><span>{{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours</span></div><h2>{{ $revision->title }}</h2><p class="content-price">{{ $revision->course->creator?->account_status === \App\Enums\AccountStatus::Deleted ? 'Free · retained creator content' : ($revision->price_kb ? $revision->price_kb.' KB' : 'Free') }}@if($revision->minimum_xp > 0) · {{ $revision->minimum_xp }} XP to unlock
@endif</p><p>{{ \Illuminate\Support\Str::limit($revision->description, 180) }}</p><span class="module-link">@can('viewAny', \App\Models\LearningCourse::class) Review this course @else @auth Explore this journey @else Sign in to explore @endauth @endcan →</span></div></a>@empty<p class="empty-catalog">No journeys match yet. <a href="{{ route('learning.catalog') }}">Try a little adventure.</a></p>@endforelse</div>{{ $courses->links() }}

</section>

@endsection
