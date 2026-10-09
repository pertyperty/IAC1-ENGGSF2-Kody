@extends('layouts.learning')
@section('title', 'Archive a course — Kody')
@section('content')
<section class="review-page page-width"><h1>Put this journey away?</h1><p>Archiving “{{ $course->publishedRevision->title }}” removes it from browsing and stops new enrollment. Existing enrollment and progress are preserved. @if($course->isWithdrawn()) Staff withdrawal remains active and blocks learner access until staff restoration. @else Existing learners keep access while no staff withdrawal is active. @endif Your adventures, revisions and review history are preserved.</p>
    @include('layouts.form-errors')
    <form method="post" action="{{ route('courses.archive', $course) }}" class="studio-form">@csrf<input type="hidden" name="record_version" value="{{ $course->record_version }}"><input type="hidden" name="confirmed" value="1"><div class="studio-actions"><button class="button button-dark">Confirm archive</button><a class="quiet-link" href="{{ route('courses.edit', $course) }}">Keep this journey</a></div></form>
</section>
@endsection
