@extends('layouts.learning')
@section('title', 'Your journeys — Kody')
@section('content')
<section class="review-page page-width journey-library"><div class="section-heading"><div><p class="overline">YOUR SAVED LEARNING</p><h1>Keep your curiosity going.</h1><p>Your lessons, progress and next steps in one place.</p></div><a class="button button-play" href="{{ route('course-learning.catalog') }}">Find a new journey →</a></div>
<h2>My courses</h2><div class="journey-cards">
@forelse($enrollments as $enrollment)
    @php($saved = $courseProgress->get($enrollment->id))
    <article class="journey-card">
    @if($enrollment->course->isWithdrawn())<h3>Journey temporarily unavailable</h3><p>Staff withdrawal is active. Your enrollment and progress are retained.</p>
    @else<h3>{{ $enrollment->revision->title }}</h3><p class="field-hint">{{ $enrollment->revision->difficulty }} · {{ $enrollment->sequential ? 'Sequential trail' : 'Open exploration' }}@if($enrollment->course->status === 'Archived') · Archived · Your access is retained @endif</p>@endif
    @include('learning.saved-progress', ['saved' => $saved])
    <dl class="journey-dates"><div><dt>Joined</dt><dd>{{ $enrollment->enrolled_at->timezone('Asia/Manila')->format('M j, Y') }}</dd></div><div><dt>Last activity</dt><dd>{{ $saved['last_activity'] ? \Carbon\CarbonImmutable::parse($saved['last_activity'])->timezone('Asia/Manila')->format('M j, Y · g:i A') : 'No lessons opened yet' }}</dd></div></dl>
    @if(!$enrollment->course->isWithdrawn())<div class="journey-card-actions">@if($saved['next'])<a class="button button-play button-small" href="{{ $saved['next']['url'] }}">Continue lesson →</a>@endif<a class="button button-secondary button-small" href="{{ route('course-learning.show', $enrollment->course_id) }}">{{ $saved['accessible'] ? 'View course' : 'Review access' }}</a></div>@endif
    </article>
@empty<x-empty-state title="No journeys yet" description="Choose a course and take your first step." :action-url="route('course-learning.catalog')" action-label="Browse courses" />@endforelse
</div>{{ $enrollments->links() }}
<h2>Recent modules</h2><p class="field-hint">Includes lessons opened inside your courses. Reading counts toward course completion; assessment wins qualify play progress.</p><div class="journey-cards">
@forelse($recentModules as $module)
    <article class="journey-card">@if($module->isWithdrawn() || $module->status !== 'Published')<h3>Adventure unavailable</h3><p>Your saved history is retained.</p>@else<h3>{{ $module->publishedRevision->title }}</h3>@endif
    @if($moduleProgress->has($module->id))@php($saved = $moduleProgress->get($module->id))@include('learning.saved-progress', ['saved' => $saved])<p class="field-hint">Latest recorded activity: {{ $saved['last_activity'] ? \Carbon\CarbonImmutable::parse($saved['last_activity'])->timezone('Asia/Manila')->format('M j, Y') : 'Not recorded' }}</p>@endif
    @if(!$module->isWithdrawn() && $module->status === 'Published')<a class="button button-secondary button-small" href="{{ route('modules.show', $module) }}">View module ↗</a>@endif</article>
@empty<p>Open a lesson to start your recent learning history.</p>@endforelse
</div></section>
@endsection
