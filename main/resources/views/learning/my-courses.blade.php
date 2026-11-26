@extends('layouts.learning')
@section('title', 'Library — Kody')
@section('content')
<section class="review-page page-width journey-library">
<x-catalog-header title="Library" description="Your courses, recent modules, and bookmarks.">
    <a class="quiet-link" href="{{ route('course-learning.catalog') }}">Browse courses</a>
</x-catalog-header>
<form method="get" action="{{ route('course-learning.mine') }}" class="catalog-search catalog-filter-panel"><div class="filter-grid"><label>Search library<input name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}" placeholder="Search titles"></label><div><label for="library-type">Show</label><select id="library-type" name="type">@foreach(['all' => 'Everything', 'courses' => 'Courses', 'modules' => 'Modules', 'bookmarks' => 'Bookmarks'] as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? 'all') === $value)>{{ $label }}</option>@endforeach</select></div></div><div class="filter-actions"><button class="button button-dark">Apply filters</button>@if(array_filter($filters))<a class="quiet-link" href="{{ route('course-learning.mine') }}">Clear filters</a>@endif</div></form>
@if(in_array($filters['type'] ?? 'all', ['all', 'courses'], true))<h2>Courses</h2><div class="journey-cards">
@forelse($enrollments as $enrollment)
    @php($saved = $courseProgress->get($enrollment->id))
    @php($isCourseBookmarked = ($bookmarkedCourseIds ?? collect())->has($enrollment->course_id))
    <article class="journey-card {{ $isCourseBookmarked ? 'is-bookmarked' : '' }}">
    @if($enrollment->course->isWithdrawn())<h3>Course temporarily unavailable</h3><p>Your enrollment and progress are saved.</p>
    @else
        <div class="journey-card-head">
            <h3>{{ $enrollment->revision->title }}</h3>
            @if($isCourseBookmarked)<span class="bookmark-pill"><x-action-icon name="bookmark" class="bookmark-icon"/>Saved</span>@endif
        </div>
        <div class="catalog-entry-meta" aria-label="Course details">
            <span class="catalog-tag">{{ $enrollment->revision->difficulty }}</span>
            <span class="catalog-tag">{{ $enrollment->sequential ? 'Sequential trail' : 'Open exploration' }}</span>
            @if($enrollment->course->status === 'Archived')<span class="catalog-tag">Archived</span>@endif
        </div>
        @if($enrollment->course->status === 'Archived')
            <p class="field-hint">Your access is retained.</p>
        @endif
    @endif
    @include('learning.saved-progress', ['saved' => $saved])
    <dl class="journey-dates"><div><dt>Joined</dt><dd>{{ $enrollment->enrolled_at->timezone('Asia/Manila')->format('M j, Y') }}</dd></div><div><dt>Last activity</dt><dd>{{ $saved['last_activity'] ? \Carbon\CarbonImmutable::parse($saved['last_activity'])->timezone('Asia/Manila')->format('M j, Y · g:i A') : 'No modules opened yet' }}</dd></div></dl>
    @if(!$enrollment->course->isWithdrawn())<div class="journey-card-actions">@if($saved['next'])<a class="button button-play button-small" href="{{ $saved['next']['url'] }}">Continue module</a>@endif<a class="button button-secondary button-small" href="{{ route('course-learning.show', $enrollment->course_id) }}">{{ $saved['accessible'] ? 'View course' : 'Review access' }}</a></div>@endif
    </article>
@empty<x-empty-state title="No matching courses" description="Browse courses or clear your search." :action-url="route('course-learning.catalog')" action-label="Browse courses" />@endforelse
</div>{{ $enrollments->links() }}
@endif
@if(in_array($filters['type'] ?? 'all', ['all', 'modules'], true))<h2>Recent modules</h2><p class="field-hint">Your latest 12 opened modules, including those in courses.</p><div class="journey-cards">
@forelse($recentModules as $module)
    @php($isModuleBookmarked = ($bookmarkedModuleIds ?? collect())->has($module->id))
    <article class="journey-card {{ $isModuleBookmarked ? 'is-bookmarked' : '' }}">@if($module->isWithdrawn() || $module->status !== 'Published')<h3>Module unavailable</h3><p>Your saved history is retained.</p>@else<div class="journey-card-head"><h3>{{ $module->publishedRevision->title }}</h3>@if($isModuleBookmarked)<span class="bookmark-pill"><x-action-icon name="bookmark" class="bookmark-icon"/>Saved</span>@endif</div>@endif
    @if($moduleProgress->has($module->id))@php($saved = $moduleProgress->get($module->id))@include('learning.saved-progress', ['saved' => $saved])<p class="field-hint">Last opened: {{ $saved['last_activity'] ? \Carbon\CarbonImmutable::parse($saved['last_activity'])->timezone('Asia/Manila')->format('M j, Y') : 'Not recorded' }}</p>@endif
    @if(!$module->isWithdrawn() && $module->status === 'Published')<a class="button button-secondary button-small" href="{{ route('modules.show', $module) }}">View module</a>@endif</article>
@empty<p>Open a module to start your recent learning history.</p>@endforelse
</div>
@endif
@if(in_array($filters['type'] ?? 'all', ['all', 'bookmarks'], true)) @include('learning.bookmarks') @endif
</section>
@endsection
