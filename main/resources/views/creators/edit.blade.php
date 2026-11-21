@extends('layouts.account')
@section('title', 'Public creator profile — Kody')
@section('content')
<h1>Public creator profile</h1><p>Share your work and experience. These fields and your username are public. Keep personal contact details and qualification documents private.</p>
@include('layouts.form-errors')
<div data-live-preview class="profile-preview-layout"><form class="account-form" method="post" action="{{ route('creator-profile.update') }}">@csrf @method('put')
<input type="hidden" name="record_version" value="{{ old('record_version', $profile?->record_version ?? 0) }}">
<label>Line of work<input name="headline" maxlength="100" value="{{ old('headline', $profile?->headline) }}" placeholder="For example: software developer and programming tutor"></label>
<label>About you<textarea name="bio" maxlength="1000" rows="4">{{ old('bio', $profile?->bio) }}</textarea></label>
<label>Experience and accomplishments<textarea name="accomplishments" maxlength="1000" rows="4">{{ old('accomplishments', $profile?->accomplishments) }}</textarea></label>
<label class="review-check"><input type="checkbox" name="confirmed" value="1" required> Publish these details on my creator profile.</label>
<button class="primary-button">Save public profile</button></form>
<aside class="live-preview-panel"><details><summary>Preview public profile</summary><article class="profile-preview" aria-label="Public profile preview"><p class="overline">UNSAVED PREVIEW</p><h2>{{ auth()->user()->username }}</h2><x-creator-byline :creator="auth()->user()" :linked="false"/><strong data-preview-value="headline" data-preview-placeholder="Your line of work"></strong><p data-preview-value="bio" data-preview-placeholder="About you"></p><h3>Experience and accomplishments</h3><p data-preview-value="accomplishments" data-preview-placeholder="Your experience"></p></article></details></aside></div>
@if(in_array(auth()->user()->account_role, [\App\Enums\Role::Instructor, \App\Enums\Role::Contributor], true))
<section><h2>Qualifications badge</h2>
@if(auth()->user()->hasCreatorBadge())<p class="notice">Your qualifications badge is active.</p>
@elseif($badgeRequest)<p class="notice">Review requested. Staff will check your private supporting documents.</p>
@else<p>Staff can review the credentials in your approved role application. Documents remain private; a badge is issued only after review.</p>
<form class="account-form" method="post" action="{{ route('creator-profile.badge-request') }}">@csrf<label><input type="checkbox" name="confirmed" value="1" required> Ask staff to review my existing qualifications.</label><button class="primary-button">Request qualifications review</button></form>
@endif</section>@endif
<p><a href="{{ route('account.show') }}">Back to account</a></p>
@endsection
