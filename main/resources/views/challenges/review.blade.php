@extends('layouts.learning')
@section('title', 'Review a coding quest — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('challenge-reviews.index') }}">← Challenge reviews</a><h1>{{ $revision->title }}</h1><p class="step-pill">Revision {{ $revision->number }} · {{ $revision->review_status }}</p>
@if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@include('challenges.problem')
<h2>Every check</h2>@foreach($revision->testCases as $case)<div class="lesson-note challenge-case"><h3>Check {{ $case->position }} · {{ $case->hidden ? 'Hidden' : 'Sample' }}</h3><p>Input</p><pre>{{ $case->input }}</pre><p>Expected output</p><pre>{{ $case->expected_output }}</pre></div>@endforeach
@if($revision->review_notes)<p class="lesson-note">{{ $revision->review_notes }}</p>@endif
@if($revision->review_status === 'Pending' && in_array($challenge->status, ['Draft','Published'], true))<form method="post" action="{{ route('challenge-reviews.review', $challenge) }}" class="studio-form">@csrf<input type="hidden" name="record_version" value="{{ $challenge->record_version }}"><label for="decision">Decision</label><select id="decision" name="decision"><option>Approved</option><option>Rejected</option></select><label for="review_notes">Feedback (required for rejection)</label><textarea id="review_notes" name="review_notes" rows="3" maxlength="255">{{ old('review_notes') }}</textarea><label><input type="checkbox" name="confirmed" value="1" required> I reviewed the problem, limits and all test cases and confirm this decision.</label><button class="button button-dark">Confirm review decision</button></form>@endif
<p class="field-hint">Approval publishes the reviewed problem and examples. Challenge submissions are coming soon.</p></section>
@endsection
