@extends('layouts.account')
@section('title', 'Delete your account — Kody')
@section('content')
<p class="eyebrow">A PERMANENT GOODBYE</p><h1>Delete your account</h1>
<p class="intro">This permanently removes your profile identity, private learning records and submissions. You cannot sign in or recover this account afterward. Private credential files are removed by background cleanup; pseudonymous audit, verified weekly scores and required financial references remain. Financial recipient records follow the accounting retention policy.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($unsettled)<p class="notice">Settle your wallet, earnings and financial requests before final deletion. <a href="{{ route('earnings.index') }}">Review them here.</a></p>@endif
@if($authored)<p class="notice">Your saved learning material and version history will remain under Deleted creator attribution and become free for new learners. Staff must inspect every retained revision for personal data.</p><p>Privacy review: {{ $privacyReview?->state ?? 'Not requested' }} · {{ $privacyReview?->review_notes }}</p>
@if($privacyReview?->state !== 'Pending')<form class="account-form" method="post" action="{{ route('creator-erasure.request') }}">@csrf<label class="checkbox-label"><input type="checkbox" name="retention_consent" value="1" required> I consent to retaining all authored learning material, making it free and attributing it to Deleted creator.</label>@include('transactions.password-confirmation')<button class="primary-button">Request a privacy review</button></form>@endif
@endif
@if(!$authored || $privacyReview?->state === 'Approved')
<form class="account-form" method="POST" action="{{ route('account.delete.store') }}">@csrf
<input type="hidden" name="profile_version" value="{{ $version }}">
@if($authored)<label class="checkbox-label"><input type="checkbox" name="retention_consent" value="1" required> Retain reviewed learning material under Deleted creator attribution with free new access.</label>@endif
<label>Type DELETE MY ACCOUNT<input name="confirmation_phrase" autocomplete="off" required></label>
<label>Current password<input type="password" name="current_password" maxlength="1024" autocomplete="current-password" required></label>
<label><input type="checkbox" name="confirmed" value="1" required> I understand this is permanent and want to delete my account.</label>
<p>Any active coding evaluation must finish first.</p><button class="primary-button" type="submit">Permanently delete my account</button></form>
@endif
<p class="secondary-link"><a href="{{ route('account.show') }}">Cancel and keep your account</a></p>
<p class="secondary-link"><a href="{{ route('account.archive') }}">Take a break with account archival</a></p>
@endsection
