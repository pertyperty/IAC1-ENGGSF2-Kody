@extends('layouts.account')
@section('title', 'Sign in — Kody')
@section('shell-class', 'account-shell-auth')
@section('content')
    <header class="auth-header">
        <div class="auth-emblem" aria-hidden="true"><x-kody-art size="64"/></div>
        <p class="overline">WELCOME BACK</p>
        <h1>Continue your journey</h1>
        <p class="intro">Sign in with your verified Kody account.</p>
    </header>
    @if ($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login.store') }}" class="account-form">
        @csrf
        <div class="auth-field">
            <label for="login-email">Email or username</label>
            <input id="login-email" type="text" name="email" autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" maxlength="100" required placeholder="you@example.com or your username">
        </div>
        <x-password-field name="password" label="Password" autocomplete="current-password" :maxlength="1024" />
        <button type="submit" class="primary-button">Sign in</button>
    </form>
    @if(app(\App\Services\Account\GoogleOAuth::class)->available())
        <div class="auth-divider" aria-hidden="true"><span>or</span></div>
        <form method="post" action="{{ route('google.start') }}" class="account-form auth-oauth-form">
            @csrf
            <button type="submit" class="button button-secondary auth-oauth-button">Continue with linked Google account</button>
        </form>
        <p class="field-hint auth-oauth-note">Already have Kody? Sign in with your password first to connect Google from your profile.</p>
    @endif
    <p class="secondary-link auth-links">
      <a href="{{ route('recovery.request') }}">Forgot your password?</a>
      <a href="{{ route('register') }}">Create an account</a>
    </p>
    <aside class="access-support">
        <h2>Think an account restriction was a mistake?</h2>
        <a class="quiet-link" href="{{ route('contact') }}#appeals"><x-action-icon name="mail"/> Contact Kody or appeal a suspension</a>
    </aside>
@endsection
