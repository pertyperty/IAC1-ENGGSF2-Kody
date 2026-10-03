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
    @if($application)<div class="notice"><b>Instructor application: {{ $application->verification_status }}</b>@if($application->verification_notes)<p>{{ $application->verification_notes }}</p>@endif</div>@endif
    @can('viewAny', \App\Models\InstructorApplication::class)<p class="secondary-link"><a href="{{ route('instructor-reviews.index') }}">Review instructor applications</a></p>@endcan
    <p class="secondary-link"><a href="{{ route('dashboard') }}">Back to your account home</a></p>
@endsection
