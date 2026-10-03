@extends('layouts.account')
@section('title', 'Recover your account — Kody')
@section('content')
    <p class="eyebrow">ACCOUNT RECOVERY</p>
    <h1>Find your way back</h1>
    <p class="intro">Request a recovery email for your verified account, or enter the code from your email.</p>
    @if ($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('recovery.send') }}" class="account-form">
        @csrf
        <label>Email address<input type="email" name="email" autocomplete="email" maxlength="100" value="{{ old('email') }}" required></label>
        <button type="submit" class="primary-button">Send recovery email</button>
    </form>
    @if(session('recovery_cooldown_until'))
        <p class="form-note" data-recovery-cooldown="{{ session('recovery_cooldown_until') }}" role="status">Wait {{ config('account.recovery.cooldown_seconds') }} seconds before requesting again.</p>
    @endif
    <p class="intro">Already have a recovery code?</p>
    <form method="post" action="{{ route('recovery.authorize') }}" class="account-form" id="recovery-token-form">
        @csrf
        <label>Recovery code<input id="recovery-token" name="recovery_token" autocomplete="off" minlength="64" maxlength="64" required></label>
        <button type="submit" class="primary-button">Continue to password reset</button>
    </form>
    <p class="secondary-link"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
