@extends('layouts.account')
@section('title', 'Verify your email — Kody')
@section('content')
    <p class="eyebrow">ONE MORE STEP</p>
    <h1>Check your inbox</h1>
    <p class="intro">Open your verification link to activate your account. Check your spam folder if it hasn’t arrived.</p>
    <form action="{{ route('verification.verify') }}" method="post" class="account-form" id="verify-link-form">
        @csrf
        <label>Verification code<input id="verification-token" name="verification_token" autocomplete="off" minlength="64" maxlength="64" required><small>If your email link doesn’t open, paste the verification code from the email.</small></label>
        @error('verification_token')<p class="form-errors" role="alert">{{ $message }}</p>@enderror
        <button class="primary-button" type="submit">Verify email</button>
    </form>
    <p>Need another link? You can request up to five links, including your registration email. Wait one minute between requests.</p>
    @if ($errors->any())<p class="form-errors" role="alert">{{ $errors->first('email') }}</p>@endif
    <form action="{{ route('verification.resend') }}" method="post" class="account-form">
        @csrf
        <label>Email address<input name="email" type="email" autocomplete="email" value="{{ old('email') }}" maxlength="100" required></label>
        <button class="primary-button" type="submit">Request verification link <span aria-hidden="true">↗</span></button>
    </form>
    <p class="secondary-link"><a href="{{ route('register') }}">Create a new account</a></p>
@endsection
