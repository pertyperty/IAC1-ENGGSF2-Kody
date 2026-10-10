@extends('layouts.account')
@section('title', 'Sign in — Kody')
@section('content')
    <p class="eyebrow">WELCOME BACK</p>
    <h1>Continue your journey</h1>
    <p class="intro">Sign in with your verified Kody account.</p>
    @if ($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login.store') }}" class="account-form">
        @csrf
        <label>Email or username<input type="text" name="email" autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" maxlength="100" required placeholder="you@example.com or your username"></label>
        <x-password-field name="password" label="Password" autocomplete="current-password" :maxlength="1024" />
        <button type="submit" class="primary-button">Sign in</button>
    </form>
    @if(app(\App\Services\Account\GoogleOAuth::class)->available())
        <form method="post" action="{{ route('google.start') }}" class="account-form">
            @csrf
            <button type="submit" class="primary-button">Continue with linked Google account</button>
        </form>
        <p>Already have Kody? Sign in with your password first to connect Google from your profile.</p>
    @endif
    <p class="secondary-link"><a href="{{ route('verification.notice') }}">Need to verify your email?</a></p>
    <p class="secondary-link"><a href="{{ route('recovery.request') }}">Forgot your password?</a></p>
    <p class="secondary-link"><a href="{{ route('register') }}">Create an account</a></p>
@endsection
