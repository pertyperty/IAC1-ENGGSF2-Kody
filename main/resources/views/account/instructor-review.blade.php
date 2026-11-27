@extends('layouts.learning')
@section('title', 'Review instructor application — Kody')
@section('content')
<section class="review-page page-width">
    <a class="quiet-link" href="{{ route('instructor-reviews.index') }}"><x-action-icon name="left"/> All applications</a>
    <p class="overline">INSTRUCTOR APPLICATION REVIEW</p>
    <h1>{{ $application->user->username }}</h1>
    <p class="profile-role-line"><x-role-badge :user="$application->user" :show-verified="true"/><span class="status-pill" data-status="{{ $application->user->account_status->value }}">{{ $application->user->account_status->value }}</span></p>
    <p>{{ $application->institution_name }} · {{ $application->specialization }}</p>
    <p>Application status: <b>{{ $application->verification_status }}</b></p>

    <x-credential-preview
        :meta="$documentMeta"
        :inline-url="route('instructor-reviews.credential', [$application, 'inline' => 1])"
        :download-url="route('instructor-reviews.credential', $application)"
        download-label="Download private credentials"
        :title="$application->user->username.' teaching credentials'"
    />

    @if($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    @if($application->verification_status === 'Pending')
        <form method="post" action="{{ route('instructor-reviews.review', $application) }}" class="account-form review-form">@csrf
            <input type="hidden" name="record_version" value="{{ $application->record_version }}">
            <label>Decision<select name="decision" required><option value="">Choose a decision</option><option value="Approved">Approve Instructor access</option><option value="Rejected">Reject application</option></select></label>
            <label>Feedback (required for rejection)<textarea name="verification_notes" maxlength="255" rows="4">{{ old('verification_notes') }}</textarea></label>
            <label class="review-check"><input type="checkbox" name="credibility_reviewed" value="1" required> I have manually reviewed the credentials and their credibility.</label>
            <p class="form-note">Approval grants Instructor access only to a verified Active Learner or Contributor. Rejection preserves existing account access.</p>
            <button type="submit" class="button button-dark">Save review</button>
        </form>
    @else
        <p class="notice">This application has been reviewed. @if($application->verification_notes){{ $application->verification_notes }}@endif</p>
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
    <details class="lesson-note">
        <summary>Submitted qualification history</summary>
        <ul class="review-list">
            @foreach($versions as $version)
                @php
                    $vMeta = $versionMeta[$version->application_version] ?? ['available' => false, 'extension' => '', 'size' => null, 'previewable' => false];
                @endphp
                <li>
                    <span>Submission {{ $version->application_version }} · {{ $version->verification_status }}@if(!empty($vMeta['extension'])) · {{ strtoupper($vMeta['extension']) }}@endif</span>
                    <span class="credential-history-actions">
                        @if($vMeta['previewable'])<a class="quiet-link" href="{{ route('instructor-reviews.version-credential', [$application, $version->application_version, 'inline' => 1]) }}" target="_blank" rel="noopener noreferrer">Preview</a>@endif
                        <a href="{{ route('instructor-reviews.version-credential', [$application, $version->application_version]) }}" data-navigation-download>Download private document</a>
                    </span>
                </li>
            @endforeach
        </ul>
        {{ $versions->links() }}
    </details>
</section>
@endsection
