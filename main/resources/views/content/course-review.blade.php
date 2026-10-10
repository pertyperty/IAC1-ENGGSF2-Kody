@extends('layouts.learning')
@section('title', 'Review a course — Kody')
@section('content')
<section class="review-page page-width">
@include('transactions.review-access')
<a class="quiet-link" href="{{ route('course-reviews.index') }}">← Course review queue</a><h1>{{ $revision->title }}</h1><p>{{ $revision->description }}</p><p>{{ $revision->category }} · {{ $revision->difficulty }} · {{ $revision->estimated_duration }} hours · {{ $revision->review_status }}</p>

    @include('layouts.form-errors')
    <p class="lesson-note">{{ $revision->sequential ? 'Sequential path: new learners complete each adventure before opening the next.' : 'Open path: new learners can explore adventures in any order.' }} Existing enrollments keep their original access policy.</p>
    @include('content.course-outline')
    @if($revision->review_status === 'Pending' && in_array($course->status, ['Draft','Published'], true))<form class="studio-form" method="post" action="{{ route('course-reviews.review', $course) }}">@csrf<input type="hidden" name="record_version" value="{{ $course->record_version }}"><label for="review_notes">Feedback (required for rejection)</label><textarea id="review_notes" name="review_notes" maxlength="255" rows="3">{{ old('review_notes') }}</textarea>@include('content.review-actions', ['approvalLabel' => 'Approve course publication'])</form>@elseif($revision->review_notes)<p class="lesson-note">{{ $revision->review_notes }}</p>@endif
</section>
@endsection
