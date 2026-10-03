@extends('layouts.account')
@section('title', 'Google sign-in — Kody')
@section('content')
    <p class="eyebrow">YOUR SIGN-IN OPTIONS</p>
    <h1>Connect your Google account</h1>
    <p class="intro">{{ $identity['linked'] ? 'Your Google account is connected.' : 'Link Google to your verified Kody account for your next visit.' }} Your Kody password remains available.</p>
    @if($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    @if($identity['linked'] || $available)
        <p>Confirm your Kody password. This change signs you out so you can sign in again securely.</p>
        <form method="post" action="{{ route($identity['linked'] ? 'google.unlink' : 'google.link') }}" class="account-form">
            @csrf
            <input type="hidden" name="profile_version" value="{{ auth()->user()->profile_version }}">
            <input type="hidden" name="identity_version" value="{{ $identity['identity_version'] }}">
            <label>Current Kody password<input type="password" name="current_password" autocomplete="current-password" maxlength="1024" required></label>
            <button type="submit" class="primary-button">{{ $identity['linked'] ? 'Unlink Google account' : 'Choose your Google account' }}</button>
        </form>
    @else
        <p>Google sign-in is coming soon. Continue using your Kody password.</p>
    @endif
    <p class="secondary-link"><a href="{{ route('account.show') }}">Back to your profile</a></p>
@endsection
