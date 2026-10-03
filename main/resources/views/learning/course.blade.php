@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('course-learning.catalog') }}">← Learning journeys</a><p class="overline">{{ $revision->category }} · {{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours</p><h1>{{ $revision->title }}</h1><p>{{ $revision->description }}</p>
@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($course->status === 'Archived')<p class="lesson-note">This journey is archived. You can keep learning because you joined before it was archived.</p>@endif
@if(!$enrollment)<form method="post" action="{{ route('course-learning.enroll', $course) }}">@csrf<input type="hidden" name="revision_id" value="{{ $revision->id }}"><button class="button button-play">Join this journey · Free →</button></form>@else<p class="lesson-note">{{ $progress->whereNotNull('completed_at')->count() }} of {{ $revision->modules->count() }} adventure assessments cleared. Lessons without assessments remain available to revisit.</p>@endif
<h2>Your trail</h2><div class="review-list">@foreach($revision->modules as $slot)
@if($enrollment && $slot->module->status === 'Published' && !$slot->module->isWithdrawn())<a href="{{ route('course-learning.lesson', [$course, $slot->id]) }}"><b>{{ $slot->position }}. {{ $slot->revision->title }}</b><span>@if($progress->get($slot->id)?->completed_at)Cleared · Play again @elseif($progress->has($slot->id))Continue your adventure @else Start this adventure @endif</span></a>
@else<div class="lesson-note"><b>{{ $slot->position }}. {{ $slot->module->isWithdrawn() ? 'Adventure unavailable' : $slot->revision->title }}</b><p>{{ $slot->module->status === 'Published' && !$slot->module->isWithdrawn() ? 'Join to explore this adventure.' : 'This adventure is currently unavailable.' }}</p></div>@endif
@endforeach</div>@include('engagement.reactions')</section>
@endsection
