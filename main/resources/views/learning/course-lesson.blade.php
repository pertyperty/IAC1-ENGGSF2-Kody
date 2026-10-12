@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width course-lesson-page lesson-page"><p class="overline">Adventure {{ $slot->position }}</p>
@if($progress->completed_at)<p class="lesson-note">{{ $revision->assessment ? 'You have already cleared this assessment. Try it again to keep practicing.' : 'You have marked this lesson as read. Revisit it whenever you like.' }}</p>@endif
@include('content.lesson', ['assessmentCompletionUrl' => route($revision->assessment && $revision->assessment['template'] === 'choice-quiz' ? 'course-learning.quiz' : 'course-learning.game', [$course, $slot->id])])
@if(!$revision->assessment && !$progress->completed_at)<form method="post" action="{{ route('course-learning.read', [$course, $slot->id]) }}">@csrf<input type="hidden" name="confirmed" value="1"><p class="field-hint">Reading saves course progress. @if($revision->type === 'Article')First module completion earns 40 XP, once across courses and standalone access.@endif It grants no streak or assessment eligibility credit.</p><button class="button button-play">Mark as read <x-action-icon name="right"/></button></form>@endif
@include('learning.journey-next')
@include('engagement.reactions')
</section>
@endsection
