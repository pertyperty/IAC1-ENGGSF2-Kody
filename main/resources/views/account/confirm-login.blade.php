@extends('layouts.account')
@section('title', 'Confirm sign-in — Kody')
@section('content')
    <p class="eyebrow">ANOTHER SESSION IS ACTIVE</p>
    <h1>Continue here?</h1>
    <p class="intro">Continuing will end the previous session. Cancelling will keep it active.</p>
    <form method="post" action="{{ route('login.confirm') }}" class="account-form">
        @csrf
        <button type="submit" name="choice" value="continue" class="primary-button">End previous session and continue</button>
        <button type="submit" name="choice" value="cancel">Cancel sign-in</button>
    </form>
@endsection
