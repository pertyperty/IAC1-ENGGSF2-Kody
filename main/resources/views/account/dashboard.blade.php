@extends('layouts.account')
@section('title', 'Welcome — Kody')
@section('content')
    <p class="eyebrow">YOUR KODY ACCOUNT</p>
    <h1>Welcome, {{ auth()->user()->first_name ?? auth()->user()->name }}.</h1>
    <p class="intro">You’re signed in to your verified account.</p>
    <p class="secondary-link"><a href="{{ route('account.show') }}">View your profile</a></p>
    <form method="post" action="{{ route('logout') }}" class="account-form">
        @csrf
        <button type="submit" class="primary-button">Sign out</button>
    </form>
@endsection
