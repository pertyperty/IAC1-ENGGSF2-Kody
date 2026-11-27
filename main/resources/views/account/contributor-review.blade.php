@extends('layouts.account')
@section('title', 'Review Contributor application — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<p class="secondary-link"><a href="{{ route('contributor-reviews.index') }}"><x-action-icon name="left"/> Back to applications</a></p>
<p class="overline">CONTRIBUTOR APPLICATION REVIEW</p>
<h1>Contributor application #{{ $application->id }}</h1>
<p class="profile-role-line"><x-role-badge :user="$application->user" :show-verified="true"/><span class="status-pill" data-status="{{ $application->user->account_status->value }}">{{ $application->user->account_status->value }}</span></p>
<p>{{ $application->user->username }} · Application status: <b>{{ $application->approval_status }}</b></p>

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<div class="lesson-note">
    <p>{{ $application->request_message }}</p>
    <p class="field-hint">At submission: {{ $application->account_age_days }} days · {{ $application->completed_modules_count }} modules · {{ $application->completed_challenges_count }} coding challenges.</p>
    @if($application->portfolio_link)<p>Portfolio: <a href="{{ $application->portfolio_link }}" target="_blank" rel="noopener noreferrer">{{ $application->portfolio_link }}</a></p>@endif
</div>

<x-credential-preview
    :meta="$documentMeta"
    :inline-url="route('contributor-reviews.credential', [$application, 'inline' => 1])"
    :download-url="route('contributor-reviews.credential', $application)"
    download-label="Download private supporting document"
    :title="$application->user->username.' supporting credentials'"
/>

@if($application->approval_status === 'Pending')
<form class="account-form" method="POST" action="{{ route('contributor-reviews.review', $application) }}">@csrf
<input type="hidden" name="record_version" value="{{ $application->record_version }}">
<label>Decision<select name="decision" required><option value="">Choose a decision</option><option>Approved</option><option>Rejected</option></select></label>
<label>Feedback (optional)<textarea name="moderator_feedback" maxlength="500" rows="3">{{ old('moderator_feedback') }}</textarea></label>
<label class="review-check"><input type="checkbox" name="confirmed" value="1" required> I reviewed the application and confirm this decision.</label>
<button class="primary-button">Confirm decision</button></form>
@else
<div class="notice"><p>This submitted application has already been decided.</p>@if($application->moderator_feedback)<p>{{ $application->moderator_feedback }}</p>@endif</div>
@endif

<p class="field-hint">Qualification documents are private. Check the name, issuing organisation, date and relevance before deciding. Record any concerns in the review.</p>
@can('verifyCreator', $application->user)
<div class="badge-status-card lesson-note">
    <div>
        <strong>Qualifications badge</strong>
        <p class="field-hint">
            @if($application->user->hasCreatorBadge())Badge active on this creator’s public profile.
            @elseif($badgeRequest)Badge review requested — waiting on staff decision.
            @else Role approval does not automatically issue a qualifications badge.
            @endif
        </p>
    </div>
    <a class="button button-secondary" href="{{ route('creator-badges.show', $application->user) }}">Review qualifications badge</a>
</div>
@endcan
@endsection
