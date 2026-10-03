@extends('layouts.account')
@section('title', 'Email verification — Kody')
@section('content')
    <p class="eyebrow">EMAIL VERIFICATION</p>
    <h1>{{ $verified ? 'You’re verified' : 'Let’s try again' }}</h1>
    <p class="intro">{{ $message }}</p>
    @if (!$verified)<a href="{{ route('verification.notice') }}" class="primary-button">Request a new link</a>@else<a href="{{ route('login') }}" class="primary-button">Sign in</a>@endif
@endsection
