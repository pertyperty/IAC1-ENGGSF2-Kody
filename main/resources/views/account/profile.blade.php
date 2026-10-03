@extends('layouts.account')
@section('title', 'Your account — Kody')
@section('content')
    <p class="eyebrow">YOUR KODY ACCOUNT</p>
    <h1>Your profile</h1>
    <p class="intro">Your account information. Your email is masked for privacy.</p>
    <dl class="profile-details">
        <dt>Username</dt><dd>{{ $profile['username'] ?? 'Not yet provided' }}</dd>
        <dt>First name</dt><dd>{{ $profile['first_name'] ?? 'Not yet provided' }}</dd>
        <dt>Last name</dt><dd>{{ $profile['last_name'] ?? 'Not yet provided' }}</dd>
        <dt>Email</dt><dd>{{ $profile['email'] }}</dd>
        <dt>Role</dt><dd>{{ $profile['role'] }}</dd>
        <dt>Status</dt><dd>{{ $profile['status'] }}</dd>
        <dt>Joined</dt><dd>{{ $profile['joined_at'] ?? 'Not available' }}</dd>
    </dl>
    <p class="secondary-link"><a href="{{ route('dashboard') }}">Back to your account home</a></p>
@endsection
