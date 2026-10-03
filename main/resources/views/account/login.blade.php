@extends('layouts.account')
@section('title', 'Sign in — Kody')
@section('content')
    <p class="eyebrow">WELCOME BACK</p>
    <h1>Continue your journey</h1>
    <p class="intro">Sign in with your verified Kody account.</p>
    @if ($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login.store') }}" class="account-form">
        @csrf
        <label>Email address<input type="email" name="email" autocomplete="username" value="{{ old('email') }}" maxlength="100" required></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" maxlength="1024" required></label>
        <button type="submit" class="primary-button">Sign in</button>
    </form>
    <p class="secondary-link"><a href="{{ route('verification.notice') }}">Need to verify your email?</a></p>
    <p class="secondary-link"><a href="{{ route('register') }}">Create an account</a></p>
@endsection
