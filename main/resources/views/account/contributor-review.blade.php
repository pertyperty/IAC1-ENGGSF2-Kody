@extends('layouts.account')
@section('title', 'Review Contributor application — Kody')
@section('content')
<h1>Contributor application #{{ $application->id }}</h1><p>{{ $application->user->name }} · {{ $application->approval_status }}</p>

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<p>{{ $application->request_message }}</p>
<p>At submission: {{ $application->account_age_days }} days · {{ $application->completed_modules_count }} modules · {{ $application->completed_challenges_count }} coding challenges.</p>
@if($application->portfolio_link)<p>Portfolio: {{ $application->portfolio_link }}</p>@endif
<p><a href="{{ route('contributor-reviews.credential', $application) }}">Download private supporting document</a></p>
@if($application->approval_status === 'Pending')
<form class="account-form" method="POST" action="{{ route('contributor-reviews.review', $application) }}">@csrf
<input type="hidden" name="record_version" value="{{ $application->record_version }}">
<label>Decision<select name="decision" required><option value="">Choose a decision</option><option>Approved</option><option>Rejected</option></select></label>
<label>Feedback (optional)<textarea name="moderator_feedback" maxlength="500">{{ old('moderator_feedback') }}</textarea></label>
<label><input type="checkbox" name="confirmed" value="1" required> I reviewed the application and confirm this decision.</label><button class="primary-button">Confirm decision</button></form>
@else<p>{{ $application->moderator_feedback }}</p><p>This submitted application has already been decided.</p>@endif
<p class="secondary-link"><a href="{{ route('contributor-reviews.index') }}">Back to applications</a></p>
@endsection
