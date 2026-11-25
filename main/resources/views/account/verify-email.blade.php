@extends('layouts.account')
@section('title', 'Verify your email — Kody')
@section('content')
    <p class="eyebrow">ONE MORE STEP</p>
    <h1>Check your inbox</h1>
    <p class="intro">Enter the six-digit code sent to your email. You can also use the secure email link. Check your spam folder if it hasn’t arrived.</p>
    <form action="{{ route('verification.code') }}" method="post" class="account-form">
        @csrf
        <label>Email address<input name="email" type="email" autocomplete="email" maxlength="100" value="{{ old('email', $email ?? '') }}" required></label>
        <label>Six-digit verification code
            <input class="verification-code-input" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                spellcheck="false" maxlength="32" required data-verification-code
                aria-describedby="verification-code-help{{ isset($codeError) ? ' verification-code-error' : '' }}"
                @isset($codeError) aria-invalid="true" @endisset>
            <small id="verification-code-help">Paste or type all six digits, including any leading zero. The code is valid for ten minutes.</small>
        </label>
        @isset($codeError)<p class="form-errors" id="verification-code-error" role="alert">{{ $codeError }}</p>@endisset
        @include('layouts.form-errors')
        <button class="primary-button" type="submit">Verify code</button>
        <button class="button button-secondary" type="submit" formaction="{{ route('verification.resend') }}" formnovalidate>Resend verification email</button>
    </form>
    <p class="field-hint">Up to five emails, including registration. Wait one minute between requests. A new email replaces previous codes and links.</p>
    <details><summary>Use the verification link token instead</summary>
    <form action="{{ route('verification.verify') }}" method="post" class="account-form" id="verify-link-form">
        @csrf
        <label>Verification link token<input id="verification-token" name="verification_token" autocomplete="off" minlength="64" maxlength="64" required><small>Paste the long token from your email if its link cannot open.</small></label>
        @error('verification_token')<p class="form-errors" role="alert">{{ $message }}</p>@enderror
        <button class="primary-button" type="submit">Verify email</button>
    </form>
    </details>
    <p class="secondary-link"><a href="{{ route('register') }}">Create a new account</a></p>
@endsection
