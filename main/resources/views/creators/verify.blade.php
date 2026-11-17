@extends('layouts.account')
@section('title', 'Review creator badge — Kody')
@section('content')
<h1>Review {{ $creator->username }}'s badge</h1><p>{{ $creator->account_role->value }} · {{ $badge?->active && $badge?->role === $creator->account_role->value ? 'Badge active' : 'No active badge for this role' }}</p>
<p>Check the applicant's identity, issuing organisation and relevance of their qualifications. A role approval does not automatically issue a badge.</p>
@if($proof)<p><a href="{{ route($creator->account_role === \App\Enums\Role::Instructor ? 'instructor-reviews.show' : 'contributor-reviews.show', $proof) }}">Open approved application and private documents</a></p>
@else<p class="notice">An approved application with supporting documents is required before granting a badge.</p>@endif
@include('layouts.form-errors')
<form class="account-form" method="post" action="{{ route('creator-badges.update', $creator) }}">@csrf @method('put')
<input type="hidden" name="record_version" value="{{ $badge?->record_version ?? 0 }}">
<input type="hidden" name="application_id" value="{{ $proof?->id }}"><input type="hidden" name="application_version" value="{{ $proof?->record_version }}">
<label for="badge-decision">Decision</label><select id="badge-decision" name="decision" required><option value="">Choose</option>@if($proof)<option value="grant">Grant qualifications badge</option>@endif @if($badge)<option value="revoke">Revoke badge</option>@endif</select>
<label class="review-check"><input type="checkbox" name="qualifications_reviewed" value="1"> I checked the supporting qualifications. Required when granting a badge.</label>
<label>Private review reason<textarea name="reason" minlength="5" maxlength="500" rows="3" required>{{ old('reason') }}</textarea></label>
@include('transactions.password-confirmation')
<button class="primary-button">Save badge decision</button></form>
@endsection
