@extends('layouts.account')
@section('title', 'Choose a new password — Kody')
@section('content')
    <p class="eyebrow">A FRESH START</p>
    <h1>Choose a new password</h1>
    <p class="intro">Changing your password ends your previous sessions. Archived accounts require a different password to reactivate.</p>
    @if ($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('recovery.complete') }}" class="account-form">
        @csrf
        <label>New password<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="32" required><small>12–32 characters, including uppercase, lowercase, a number and a symbol.</small></label>
        <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="32" required></label>
        <button type="submit" class="primary-button">Change password</button>
    </form>
@endsection
