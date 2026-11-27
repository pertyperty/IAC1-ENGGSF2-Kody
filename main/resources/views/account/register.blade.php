@extends('layouts.account')
@section('title', 'Create your account — Kody')
@section('shell-class', 'account-shell-auth')
@section('content')
<header class="auth-header">
    <div class="auth-emblem" aria-hidden="true"><x-kody-art size="64"/></div>
    <p class="overline">{{ ($forEducator ?? false) ? 'EDUCATOR REGISTRATION' : 'JOIN KODY' }}</p>
    <h1>Create your account</h1>
    @if($fromTower ?? false)
        <p class="intro">Create a free account to play beyond the three Tower trials. Trial clears stay in this browser.</p>
    @elseif($forEducator ?? false)
        <p class="intro">Start with a verified Kody account, then submit your teaching credentials from your profile to unlock Instructor tools.</p>
    @else
        <p class="intro">Save your progress and continue learning.</p>
    @endif
</header>
@if($forEducator ?? false)
    <aside class="access-support auth-role-guide" aria-label="Educator onboarding steps">
        <h2>How educator access works</h2>
        <ol class="auth-steps">
            <li>Create and verify your free account below.</li>
            <li>Sign in, open <strong>Your profile</strong>, and select <strong>Apply as an instructor</strong>.</li>
            <li>Once staff review your credentials, unlock Module Studio, Course Builder, and Challenge Studio.</li>
        </ol>
    </aside>
@endif
@include('layouts.form-errors')
<form action="{{ route('register.store') }}" method="post" class="account-form">
    @csrf
    <div class="auth-field">
        <label for="username">Username (required)</label>
        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" minlength="6" maxlength="30" aria-describedby="username-help" required placeholder="Choose a unique username">
        <small id="username-help">6–30 characters and case-sensitive.</small>
    </div>
    <div class="auth-field">
        <label for="email">Email address (required)</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="100" aria-describedby="email-help" required placeholder="you@example.com">
        <small id="email-help">Use your email address for verification.</small>
    </div>
    <x-password-field name="password" label="Password (required)" :minlength="12" hint="12–32 characters, including uppercase, lowercase, a number and a symbol." />
    <x-password-field name="password_confirmation" label="Confirm password (required)" :minlength="12"/>
    <input type="hidden" name="terms_version" value="{{ \App\Support\RegistrationTerms::VERSION }}">
    <label class="checkbox-row terms-agreement" for="terms_accepted"><input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" required> <span>I agree to the <a href="{{ route('legal.terms') }}">Terms and conditions</a>.</span></label>
    <p class="form-note">Read how we use your information in the <a href="{{ route('legal.privacy') }}">Privacy notice</a>.</p>
    <button type="submit" class="primary-button">Create account</button>
</form>
<p class="secondary-link auth-links">
    <a href="{{ route('login') }}">Already have an account? Sign in</a>
    @unless($forEducator ?? false)
        <a href="{{ route('register', ['role' => 'educator']) }}">Joining as an educator?</a>
    @endunless
</p>
@endsection
