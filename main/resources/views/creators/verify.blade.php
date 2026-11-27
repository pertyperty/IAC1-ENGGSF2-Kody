@extends('layouts.account')
@section('title', 'Review creator badge — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<nav class="studio-wayfinding" aria-label="Badge review navigation">
    <a href="{{ route('review-inbox', ['category' => 'badges']) }}">Review inbox</a>
    <a href="{{ route('account-governance.show', $creator) }}">Account governance</a>
    <a href="{{ route('creators.show', $creator) }}">Public profile</a>
</nav>
<p class="overline">QUALIFICATIONS BADGE REVIEW</p>
<h1>Review {{ $creator->username }}'s badge</h1>
<p class="profile-role-line"><x-role-badge :user="$creator" :show-verified="true"/></p>
<p>{{ $creator->account_role->value }} · <strong>{{ $badge?->active && $badge?->role === $creator->account_role->value ? 'Badge active' : 'No active badge for this role' }}</strong></p>
<p>Check the applicant's identity, issuing organisation and relevance of their qualifications. A role approval does not automatically issue a badge.</p>

@if($badgeRequest)
    <div class="notice"><strong>Pending qualifications review request</strong><p>Submitted {{ \Carbon\CarbonImmutable::parse($badgeRequest->requested_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila.</p></div>
@endif

@if($proof)
    <section class="lesson-note" aria-label="Approved application summary">
        <h2>Approved {{ $creator->account_role->value }} application</h2>
        @if($creator->account_role === \App\Enums\Role::Instructor)
            <p><strong>{{ $proof->institution_name }}</strong> · {{ $proof->specialization }}</p>
        @else
            <p>{{ $proof->request_message }}</p>
            <p class="field-hint">Milestones at submission: {{ $proof->account_age_days }} days · {{ $proof->completed_modules_count }} modules · {{ $proof->completed_challenges_count }} challenges.</p>
            @if($proof->portfolio_link)<p>Portfolio: <a href="{{ $proof->portfolio_link }}" target="_blank" rel="noopener noreferrer">{{ $proof->portfolio_link }}</a></p>@endif
        @endif
        <p><a class="quiet-link" href="{{ route($creator->account_role === \App\Enums\Role::Instructor ? 'instructor-reviews.show' : 'contributor-reviews.show', $proof) }}">Open approved application and private documents</a></p>
    </section>

    <x-credential-preview
        :meta="$documentMeta"
        :inline-url="$proofInlineUrl"
        :download-url="$proofDownloadUrl"
        download-label="Download private document"
        :title="$creator->username.' supporting credentials'"
    />
@else
    <p class="notice">An approved application with supporting documents is required before granting a badge.</p>
@endif

@include('layouts.form-errors')
<form class="account-form" method="post" action="{{ route('creator-badges.update', $creator) }}">@csrf @method('put')
<input type="hidden" name="requested_at" value="{{ $badgeRequest?->requested_at }}">
<input type="hidden" name="record_version" value="{{ $badge?->record_version ?? 0 }}">
<input type="hidden" name="application_id" value="{{ $proof?->id }}"><input type="hidden" name="application_version" value="{{ $proof?->record_version }}">
<label for="badge-decision">Decision</label><select id="badge-decision" name="decision" required><option value="">Choose</option>@if($badgeRequest)<option value="decline">Decline badge request</option>@endif
@if($proof)<option value="grant">Grant qualifications badge</option>@endif @if($badge && $badge->active)<option value="revoke">Revoke badge</option>@endif</select>
<label class="review-check"><input type="checkbox" name="qualifications_reviewed" value="1"> I checked the supporting qualifications. Required when granting a badge.</label>
<label>Private review reason<textarea name="reason" minlength="5" maxlength="500" rows="3" required>{{ old('reason') }}</textarea><small>Stored in the private staff audit log only; never shown to learners or the creator.</small></label>
@include('transactions.password-confirmation')
<button class="primary-button">Save badge decision</button></form>
@endsection
