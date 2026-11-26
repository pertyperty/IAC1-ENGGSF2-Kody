@extends('layouts.account')
@section('title', 'Become a Contributor — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<p class="eyebrow">CONTRIBUTOR APPLICATION</p>
<h1>Become a Contributor</h1>
<p class="intro">Share what you know by creating coding challenges for learners. Your Learner access stays available while staff review your application.</p>
@include('layouts.local-role-demo')

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

@php
    $ageMet = $progress['account_age_days'] >= 30;
    $modulesMet = $progress['completed_modules_count'] >= 25;
    $challengesMet = $progress['completed_challenges_count'] >= 50;
@endphp
<section aria-labelledby="contributor-milestones-heading">
    <h2 id="contributor-milestones-heading">Eligibility milestones</h2>
    <div class="milestone-grid">
        <article class="milestone-card {{ $ageMet ? 'is-met' : '' }}">
            <div class="milestone-head">
                <strong>Account age</strong>
                <span class="status-pill" data-status="{{ $ageMet ? 'Active' : 'Unverified' }}">{{ $ageMet ? 'Met' : (30 - $progress['account_age_days']).' days left' }}</span>
            </div>
            <p class="milestone-value">{{ $progress['account_age_days'] }} / 30 days</p>
            <progress max="30" value="{{ min(30, $progress['account_age_days']) }}"></progress>
        </article>
        <article class="milestone-card {{ $modulesMet ? 'is-met' : '' }}">
            <div class="milestone-head">
                <strong>Modules completed</strong>
                <span class="status-pill" data-status="{{ $modulesMet ? 'Active' : 'Unverified' }}">{{ $modulesMet ? 'Met' : (25 - $progress['completed_modules_count']).' remaining' }}</span>
            </div>
            <p class="milestone-value">{{ $progress['completed_modules_count'] }} / 25</p>
            <progress max="25" value="{{ min(25, $progress['completed_modules_count']) }}"></progress>
            @unless($modulesMet)<a class="quiet-link" href="{{ route('learning.catalog') }}">Browse modules</a>@endunless
        </article>
        <article class="milestone-card {{ $challengesMet ? 'is-met' : '' }}">
            <div class="milestone-head">
                <strong>Coding challenges passed</strong>
                <span class="status-pill" data-status="{{ $challengesMet ? 'Active' : 'Unverified' }}">{{ $challengesMet ? 'Met' : (50 - $progress['completed_challenges_count']).' remaining' }}</span>
            </div>
            <p class="milestone-value">{{ $progress['completed_challenges_count'] }} / 50</p>
            <progress max="50" value="{{ min(50, $progress['completed_challenges_count']) }}"></progress>
            @unless($challengesMet)<a class="quiet-link" href="{{ route('challenges.catalog') }}">Browse challenges</a>@endunless
        </article>
    </div>
    <p class="field-hint">Each module and coding challenge counts once. Landing-page practice does not count toward this application.</p>
</section>

@unless(config('judge0.enabled'))<p class="notice">Coding challenge evaluation is currently unavailable. Tower clears do not count as Coding challenge passes.</p>@endunless
@if($pendingInstructor)<p class="notice">Your Instructor application is awaiting review. Finish that review before starting another role application.</p>@endif
@if($canApply)
<form class="account-form" method="POST" action="{{ route('contributor-application.store') }}" enctype="multipart/form-data">@csrf
<input type="hidden" name="previous_application_id" value="{{ $latest?->id ?? 0 }}">
<label>Why would you like to contribute?<textarea name="request_message" maxlength="500" rows="4" required placeholder="Share the programming topics you want to author challenges for">{{ old('request_message') }}</textarea></label>
<label>Portfolio link (optional)<input type="url" name="portfolio_link" maxlength="255" value="{{ old('portfolio_link') }}" placeholder="https://github.com/username"></label>
<label>Supporting credentials<input type="file" name="credential_document" accept=".pdf,.jpg,.jpeg,.png" required><small>PDF, JPEG or PNG, up to {{ config('account.credentials.max_kilobytes') }} KB. Only authorized reviewers can download your private document.</small></label>
<label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required> I confirm these application details.</label>
<button class="primary-button">Submit for review</button></form>
@elseif(!$progress['eligible'] && !$latest)<p class="notice">Complete all three milestones before applying.</p>@endif

@if($history->isNotEmpty())
<section class="application-history" aria-labelledby="contributor-history-heading">
    <h2 id="contributor-history-heading">Your application history</h2>
    <div class="review-list">
        @foreach($history as $application)
            <div class="lesson-note">
                <b>Application #{{ $application->id }} · {{ $application->approval_status }}</b>
                <p>{{ $application->request_message }}</p>
                @if($application->moderator_feedback)<p>Reviewer feedback: {{ $application->moderator_feedback }}</p>@endif
            </div>
        @endforeach
    </div>
    {{ $history->links() }}
</section>
@endif
<p class="secondary-link"><a href="{{ route('account.show') }}">Back to your account</a></p>
@endsection
