@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('course-learning.show', $course) }}">← Your course trail</a><p class="overline">Adventure {{ $slot->position }}</p>
@if($progress->completed_at)<p class="lesson-note">You have already cleared this assessment. Try it again to keep practicing.</p>@endif
@include('content.lesson', ['assessmentCompletionUrl' => route($revision->assessment && $revision->assessment['template'] === 'choice-quiz' ? 'course-learning.quiz' : 'course-learning.game', [$course, $slot->id])])
@include('engagement.reactions')
</section>
@endsection
