@extends('layouts.account')
@section('title', 'Public creator profile — Kody')
@section('content')
<h1>Public creator profile</h1><p>Share your work and experience. These fields and your username are public. Keep personal contact details and qualification documents private.</p>
@include('layouts.form-errors')
<form class="account-form" method="post" action="{{ route('creator-profile.update') }}">@csrf @method('put')
<input type="hidden" name="record_version" value="{{ old('record_version', $profile?->record_version ?? 0) }}">
<label>Line of work<input name="headline" maxlength="100" value="{{ old('headline', $profile?->headline) }}" placeholder="For example: software developer and programming tutor"></label>
<label>About you<textarea name="bio" maxlength="1000" rows="4">{{ old('bio', $profile?->bio) }}</textarea></label>
<label>Experience and accomplishments<textarea name="accomplishments" maxlength="1000" rows="4">{{ old('accomplishments', $profile?->accomplishments) }}</textarea></label>
<label class="review-check"><input type="checkbox" name="confirmed" value="1" required> Publish these details on my creator profile.</label>
<button class="primary-button">Save public profile</button></form>
<p><a href="{{ route('account.show') }}">Back to account</a></p>
@endsection
