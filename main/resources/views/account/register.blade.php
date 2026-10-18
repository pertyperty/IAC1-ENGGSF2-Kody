@extends('layouts.account')
@section('title', 'Create your account — Kody')
@section('content')
<p class="eyebrow">START WITH CURIOSITY</p><h1>Your next discovery starts here</h1>
<p class="intro">Save your Tower progress and explore creator lessons. Your username is your public identity; personal names are optional later.</p>
@include('layouts.form-errors')
<form action="{{ route('register.store') }}" method="post" class="account-form">
    @csrf
    <label for="username">Username (required)</label>
    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" minlength="6" maxlength="30" aria-describedby="username-help" required>
    <small id="username-help">6–30 characters; unique and case-sensitive. Use this exact spelling to sign in. Example: curious_coder. Existing usernames remain valid.</small>
    <label for="email">Email address (required)</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="100" aria-describedby="email-help" required>
    <small id="email-help">Up to 100 characters. Use an address you can verify; email sign-in ignores letter case.</small>
    <x-password-field name="password" label="Password (required)" :minlength="12" hint="12–32 characters, including uppercase, lowercase, a number and a symbol." />
    <x-password-field name="password_confirmation" label="Confirm password (required)" :minlength="12" hint="Enter the same password again." />
    <button type="submit" class="primary-button">Create account <x-action-icon name="right"/></button>
    <p class="form-note">Verify your email before signing in. Want to teach? Apply from your verified profile; no credential upload is needed here.</p>
</form>
<p class="secondary-link"><a href="{{ route('verification.notice') }}">Already registered? Request verification.</a></p>
<p class="secondary-link"><a href="{{ route('login') }}">Already verified? Sign in.</a></p>
@endsection
