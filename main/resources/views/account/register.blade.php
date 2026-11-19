@extends('layouts.account')
@section('title', 'Create your account — Kody')
@section('content')
<h1>Create your account</h1>
<p class="intro">Save your progress and continue learning.</p>
@include('layouts.form-errors')
<form action="{{ route('register.store') }}" method="post" class="account-form">
    @csrf
    <label for="username">Username (required)</label>
    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" minlength="6" maxlength="30" aria-describedby="username-help" required>
    <small id="username-help">6–30 characters; unique and case-sensitive. Use this exact spelling to sign in. Example: curious_coder. Existing usernames remain valid.</small>
    <label for="email">Email address (required)</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="100" aria-describedby="email-help" required>
    <small id="email-help">Use an email address you can verify.</small>
    <x-password-field name="password" label="Password (required)" :minlength="12" hint="12–32 characters, including uppercase, lowercase, a number and a symbol." />
    <x-password-field name="password_confirmation" label="Confirm password (required)" :minlength="12" hint="Enter the same password again." />
    <input type="hidden" name="terms_version" value="{{ \App\Support\RegistrationTerms::VERSION }}">
    <label class="checkbox-row terms-agreement" for="terms_accepted"><input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" required> <span>I agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Terms and conditions</a>.</span></label>
    <p class="form-note">Read how we use your information in the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Privacy notice</a>.</p>
    <button type="submit" class="primary-button">Create account</button>
    <p class="form-note">Next: verify your email to sign in.</p>
</form>
<p class="secondary-link"><a href="{{ route('verification.notice') }}">Resend verification email</a></p>
<p class="secondary-link"><a href="{{ route('login') }}">Already have an account? Sign in</a></p>
@endsection
