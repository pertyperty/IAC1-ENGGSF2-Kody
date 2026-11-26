@extends('layouts.account')
@section('title', 'Apply as an Instructor — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
@include('layouts.local-role-demo')
<p class="eyebrow">INSTRUCTOR APPLICATION</p>
<h1>Apply to become an Instructor</h1>
<p class="intro">Share your teaching credentials for staff review. Your current learning access stays active while your application is checked.</p>

<ul class="creator-steps role-benefits" aria-label="What Instructors can do">
    <li><strong>Build interactive modules</strong><span>Author articles, videos, coding games, and graded practice quizzes.</span></li>
    <li><strong>Organize guided courses</strong><span>Combine your approved modules into structured sequential or open courses.</span></li>
    <li><strong>Publish coding challenges &amp; profile</strong><span>Create coding problems, share your public Instructor profile, and request a staff-reviewed qualifications badge.</span></li>
</ul>

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($application)<div class="notice"><strong>Current application status: {{ $application->verification_status }}</strong>@if($application->verification_notes)<p>{{ $application->verification_notes }}</p>@endif</div>@endif
@if($pendingContributor)<p class="notice">Your Contributor application is awaiting review. Wait for its decision before applying for Instructor access.</p>
@elseif(!$application || $application->verification_status === 'Rejected')
<form class="account-form" method="POST" enctype="multipart/form-data" action="{{ route('instructor-application.store') }}">@csrf
<input type="hidden" name="record_version" value="{{ $application?->record_version ?? 0 }}">
<div class="form-row">
    <label>Institution<input name="institution_name" value="{{ old('institution_name') }}" maxlength="100" required placeholder="School, university, or organization"></label>
    <label>Specialization<input name="specialization" value="{{ old('specialization') }}" maxlength="100" required placeholder="e.g. Computer Science, Web Development"></label>
</div>
<label>Teaching credentials<input type="file" name="credential_document" accept=".pdf,.jpg,.jpeg,.png" required><small>PDF, PNG or JPEG; up to {{ number_format(config('account.credentials.max_kilobytes') / 1024, 1) }} MB. Stored privately for authorized staff reviewers only.</small></label>
<label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required> I confirm these credentials are accurate and ready for staff review.</label>
<button class="primary-button">{{ $application ? 'Submit a new application version' : 'Apply to become a creator' }}</button></form>
@else<p class="lesson-note">Your application is awaiting staff review. Another submission is available only if an application is returned.</p>@endif

<section class="application-history" aria-labelledby="instructor-history-heading">
    <h2 id="instructor-history-heading">Your application history</h2>
    <div class="review-list">
        @forelse($history as $version)
            <div class="lesson-note">
                <b>Submission {{ $version->application_version }} · {{ $version->verification_status }}</b>
                @if($version->verification_notes)<p>{{ $version->verification_notes }}</p>@endif
            </div>
        @empty
            <p class="field-hint">Your submitted Instructor applications and review decisions will appear here.</p>
        @endforelse
    </div>
    @if($history instanceof \Illuminate\Contracts\Pagination\Paginator){{ $history->links() }}@endif
</section>
<p class="secondary-link"><a href="{{ route('account.show') }}">Back to your profile</a></p>
@endsection
