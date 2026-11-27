@extends('layouts.account')
@section('title', 'Public creator profile — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<h1>Public creator profile</h1><p>Share your qualifications, bio, and public links. These fields and your username are public. Keep personal contact details and qualification documents private.</p>
@include('layouts.form-errors')
<div data-live-preview class="profile-preview-layout"><form class="account-form" method="post" action="{{ route('creator-profile.update') }}">@csrf @method('put')
<input type="hidden" name="record_version" value="{{ old('record_version', $profile?->record_version ?? 0) }}">
<label>Line of work<input name="headline" maxlength="100" value="{{ old('headline', $profile?->headline) }}" placeholder="For example: software developer and programming tutor"></label>
<label>Qualifications and credentials (public summary)<textarea name="qualifications" maxlength="1000" rows="3" placeholder="Degrees, teaching certifications, or relevant professional credentials">{{ old('qualifications', $profile?->qualifications) }}</textarea><small>Optional public summary shown on your profile. Private uploaded verification files are never exposed.</small></label>
<label>About you<textarea name="bio" maxlength="1000" rows="4">{{ old('bio', $profile?->bio) }}</textarea></label>
<label>Experience and accomplishments<textarea name="accomplishments" maxlength="1000" rows="4">{{ old('accomplishments', $profile?->accomplishments) }}</textarea></label>
<fieldset><legend>Public socials and portfolio links (optional)</legend>
<p class="field-hint">Use full <code>https://</code> links you want learners to visit.</p>
<div class="form-row">
<label>Website<input type="url" name="social_links[website]" maxlength="255" value="{{ old('social_links.website', $socials['website']['url'] ?? '') }}" placeholder="https://example.com"></label>
<label>GitHub<input type="url" name="social_links[github]" maxlength="255" value="{{ old('social_links.github', $socials['github']['url'] ?? '') }}" placeholder="https://github.com/username"></label>
<label>LinkedIn<input type="url" name="social_links[linkedin]" maxlength="255" value="{{ old('social_links.linkedin', $socials['linkedin']['url'] ?? '') }}" placeholder="https://linkedin.com/in/username"></label>
<label>X<input type="url" name="social_links[x]" maxlength="255" value="{{ old('social_links.x', $socials['x']['url'] ?? '') }}" placeholder="https://x.com/username"></label>
</div></fieldset>
<label class="review-check"><input type="checkbox" name="confirmed" value="1" required> Publish these details on my creator profile.</label>
<button class="primary-button">Save public profile</button></form>
<aside class="live-preview-panel"><details><summary>Preview public profile</summary><article class="profile-preview" aria-label="Public profile preview"><p class="overline">UNSAVED PREVIEW</p><h2>{{ auth()->user()->username }}</h2><x-creator-byline :creator="auth()->user()" :linked="false"/><strong data-preview-value="headline" data-preview-placeholder="Your line of work"></strong><h3>Qualifications</h3><p data-preview-value="qualifications" data-preview-placeholder="Your public qualifications"></p><h3>About you</h3><p data-preview-value="bio" data-preview-placeholder="About you"></p><h3>Experience and accomplishments</h3><p data-preview-value="accomplishments" data-preview-placeholder="Your experience"></p></article></details></aside></div>
@if(in_array(auth()->user()->account_role, [\App\Enums\Role::Instructor, \App\Enums\Role::Contributor], true))
<section id="qualifications-badge" class="badge-request-section"><h2>Qualifications badge</h2>
@if(auth()->user()->hasCreatorBadge())
<div class="notice">
    <p class="profile-role-line"><x-role-badge :user="auth()->user()" :show-verified="true"/></p>
    <p>Your qualifications badge is active and visible on your public profile and published work.</p>
    <p><a class="quiet-link" href="{{ route('creators.show', auth()->user()) }}">View public creator profile</a></p>
</div>
@elseif($badgeRequest)
<div class="notice"><p><strong>Review requested.</strong> Staff will check your private supporting documents.</p><p class="field-hint">Requested {{ \Carbon\CarbonImmutable::parse($badgeRequest->requested_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila.</p></div>
@elseif($proof && $documentAvailable)
<p>Staff can review the private credentials in your approved {{ auth()->user()->account_role->value }} application. Documents remain private; once approved, a verified badge appears on your public profile and published work.</p>
<form class="account-form" method="post" action="{{ route('creator-profile.badge-request') }}">@csrf<label class="review-check"><input type="checkbox" name="confirmed" value="1" required> Ask staff to review my existing qualifications.</label><button class="primary-button">Request qualifications review</button></form>
@else
<p class="notice">A qualifications badge requires an approved {{ auth()->user()->account_role->value }} application with available private supporting documents on file. Contact support if your supporting file needs to be refreshed.</p>
@endif</section>@endif
<p class="secondary-link"><a href="{{ route('account.show') }}">Back to account</a></p>
@endsection
