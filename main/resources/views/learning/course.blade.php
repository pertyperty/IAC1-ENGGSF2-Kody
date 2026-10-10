@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width course-trail-page">
<p class="overline">{{ $revision->category }} · {{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours</p><h1>{{ $revision->title }}</h1><p>{{ $revision->description }}</p>

@include('layouts.form-errors')
@if($course->status === 'Archived')<p class="lesson-note">This journey is archived. You can keep learning because you joined before it was archived.</p>@endif
@if(!$accessible && !$requirements['eligible'])<p class="lesson-note">Unlock requirement: {{ $requirements['minimum_xp'] }} XP. Adventures still to clear: {{ implode(', ', $requirements['missing_titles']) ?: 'none' }}.</p>@endif
@if(!$accessible)
<form method="post" action="{{ route('course-learning.enroll', $course) }}">
    @csrf<input type="hidden" name="revision_id" value="{{ $revision->id }}">
    @if($price > 0)<label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required> Confirm spending {{ $price }} KodeBits.</label>@endif
    <button class="button button-play" @disabled(!$requirements['eligible'])>Join this journey · {{ $price ? $price.' KB' : 'Free' }} →</button>
</form>
@else
<p class="field-hint">{{ $enrollment->sequential ? 'Complete each adventure to unlock the next.' : 'Explore the adventures in any order.' }}</p>
@endif
@if(!$enrollment && $revision->sequential)<p class="lesson-note">This journey unlocks one adventure at a time as you complete each lesson.</p>@endif
@if($accessible)@include('learning.journey-next')@endif
<h2>Your trail</h2>@include('learning.course-trail')
@include('engagement.reactions')</section>
@endsection
