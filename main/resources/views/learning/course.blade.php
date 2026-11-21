@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width course-trail-page">
<p class="overline">{{ $revision->category }} · {{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours</p><h1>{{ $revision->title }}</h1><x-creator-byline :creator="$course->creator"/><x-bookmark kind="course" :content="$course->id"/><p>{{ $revision->description }}</p>

@include('layouts.form-errors')
@if($course->status === 'Archived')<p class="lesson-note">This course is archived. You can keep learning because you joined before it was archived.</p>@endif
@if(!$accessible && !$requirements['eligible'])<p class="lesson-note">Unlock requirement: {{ $requirements['minimum_xp'] }} XP. Modules still to complete: {{ implode(', ', $requirements['missing_titles']) ?: 'none' }}.</p>@endif
@if(!$accessible)
<form method="post" action="{{ route('course-learning.enroll', $course) }}">
    @csrf<input type="hidden" name="revision_id" value="{{ $revision->id }}">
    @if($price > 0)<label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required> Confirm spending {{ $price }} KodeBits.</label>@endif
    <button class="button button-play" @disabled(!$requirements['eligible'])>Enroll in course · {{ $price ? $price.' KB' : 'Free' }}</button>
</form>
@else
<p class="field-hint">{{ $enrollment->sequential ? 'Complete each module to unlock the next.' : 'Open modules in any order.' }}</p>
@endif
@if(!$enrollment && $revision->sequential)<p class="lesson-note">Complete modules in order to unlock the next.</p>@endif
@if($accessible)@include('learning.journey-next')@endif
<h2>Lessons</h2>@include('learning.course-trail')
@include('engagement.reactions')</section>
@endsection
