@extends('layouts.account')
@section('title', 'Create your account — Kody')
@section('content')
<h1>Create your account</h1>
@if($fromTower ?? false)
    <p class="intro">Create a free account to play beyond the three Tower trials. Trial clears stay in this browser.</p>
@else
    <p class="intro">Save your progress and continue learning.</p>
@endif
@include('layouts.form-errors')
<form action="{{ route('register.store') }}" method="post" class="account-form">
    @csrf
    <label for="username">Username (required)</label>
    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" minlength="6" maxlength="30" aria-describedby="username-help" required>
    <small id="username-help">6–30 characters and case-sensitive.</small>
    <label for="email">Email address (required)</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="100" aria-describedby="email-help" required>
    <small id="email-help">Use your email address.</small>
    <x-password-field name="password" label="Password (required)" :minlength="12" hint="12–32 characters, including uppercase, lowercase, a number and a symbol." />
    <x-password-field name="password_confirmation" label="Confirm password (required)" :minlength="12"/>
    <input type="hidden" name="terms_version" value="{{ \App\Support\RegistrationTerms::VERSION }}">
    <label class="checkbox-row terms-agreement" for="terms_accepted"><input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" required> <span>I agree to the <a href="{{ route('legal.terms') }}">Terms and conditions</a>.</span></label>
    <p class="form-note">Read how we use your information in the <a href="{{ route('legal.privacy') }}">Privacy notice</a>.</p>
    <button type="submit" class="primary-button">Create account</button>
</form>
<p class="secondary-link"><a href="{{ route('login') }}">Already have an account? Sign in</a></p>
@endsection
